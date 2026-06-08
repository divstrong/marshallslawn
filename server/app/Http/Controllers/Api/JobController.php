<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobMediaResource;
use App\Http\Resources\JobResource;
use App\Http\Resources\MessageResource;
use App\Models\Employee;
use App\Models\Job;
use App\Models\JobMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

/**
 * Service jobs. Foreman and Field users only see jobs on their crews;
 * Estimators see every job (read-only).
 */
class JobController extends Controller
{
    /**
     * GET /api/jobs?filter=all|scheduled|in_progress|completed
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $query = Job::query()->with(['property', 'customer', 'crew']);

        if ($employee->role !== 'estimator') {
            $query->whereIn('crew_id', $employee->crewIds());
        }

        $filter = (string) $request->query('filter', 'all');
        if (in_array($filter, ['scheduled', 'in_progress', 'completed'], true)) {
            $query->where('status', $filter);
        }

        $jobs = $query->orderByDesc('scheduled_date')->orderByDesc('id')->limit(200)->get();

        return JobResource::collection($jobs);
    }

    /**
     * GET /api/jobs/{job}
     */
    public function show(Request $request, Job $job): JobResource
    {
        $this->authorizeView($request->user(), $job);

        $job->load([
            'property',
            'customer',
            'crew',
            'jobServices.service',
            'messages' => fn ($q) => $q->latest()->limit(20),
            'media' => fn ($q) => $q->latest(),
        ]);

        return new JobResource($job);
    }

    /**
     * POST /api/jobs/{job}/start — foreman clocks arrival at the job.
     */
    public function start(Request $request, Job $job): JobResource
    {
        $this->authorizeForeman($request->user(), $job);

        $job->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'finished_at' => null,
            'completed_date' => null,
        ]);

        return new JobResource($job->fresh(['property', 'customer', 'crew']));
    }

    /**
     * POST /api/jobs/{job}/complete — foreman clocks the job done.
     */
    public function complete(Request $request, Job $job): JobResource
    {
        $this->authorizeForeman($request->user(), $job);

        $job->update([
            'status' => 'completed',
            'started_at' => $job->started_at ?? now(),
            'finished_at' => now(),
            'completed_date' => now()->toDateString(),
        ]);

        return new JobResource($job->fresh(['property', 'customer', 'crew']));
    }

    /**
     * POST /api/jobs/{job}/notes — add a note to the job thread.
     */
    public function addNote(Request $request, Job $job): MessageResource
    {
        /** @var Employee $employee */
        $employee = $request->user();
        $this->authorizeView($employee, $job);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = $job->messages()->create([
            'sender_type' => Employee::class,
            'sender_id' => $employee->id,
            'body' => $data['body'],
            'channel' => 'app',
        ]);

        return new MessageResource($message);
    }

    /**
     * POST /api/jobs/{job}/media — upload a job photo.
     */
    public function storeMedia(Request $request, Job $job): JsonResponse
    {
        $this->authorizeView($request->user(), $job);

        $request->validate([
            'photo' => ['required', 'file', 'max:15360'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('photo');
        $mime = (string) $file->getMimeType();
        abort_unless(str_starts_with($mime, 'image/'), 422, 'The upload must be an image.');

        $path = $file->store('job-media', 'public');

        $media = $job->media()->create([
            'uploaded_by' => null,
            'filename' => basename($path),
            'original_name' => $file->getClientOriginalName() ?: basename($path),
            'mime_type' => $mime,
            'size' => $file->getSize(),
            'disk' => 'public',
            'path' => $path,
            'type' => 'photo',
            'notes' => $request->input('notes'),
        ]);

        return response()->json(['data' => new JobMediaResource($media)], 201);
    }

    /**
     * DELETE /api/jobs/{job}/media/{media} — remove a job photo.
     */
    public function destroyMedia(Request $request, Job $job, JobMedia $media): JsonResponse
    {
        $this->authorizeView($request->user(), $job);
        abort_unless((int) $media->job_id === (int) $job->id, 404);

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Estimators may read any job; everyone else is limited to their crews.
     */
    private function authorizeView(Employee $employee, Job $job): void
    {
        if ($employee->role === 'estimator') {
            return;
        }

        abort_unless(
            $employee->crewIds()->contains($job->crew_id),
            403,
            'This job is not assigned to your crew.',
        );
    }

    /**
     * Only a foreman of the job's crew may run the job clock.
     */
    private function authorizeForeman(Employee $employee, Job $job): void
    {
        abort_unless(
            $employee->role === 'foreman' && $employee->crewIds()->contains($job->crew_id),
            403,
            'Only the crew foreman can start or complete this job.',
        );
    }
}
