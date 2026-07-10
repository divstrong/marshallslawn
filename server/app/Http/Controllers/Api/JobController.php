<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobMediaResource;
use App\Http\Resources\JobResource;
use App\Http\Resources\MessageResource;
use App\Models\Employee;
use App\Models\Job;
use App\Models\JobMedia;
use App\Models\JobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Http;
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

        // jobServices feed the app's client-side search (by service name).
        $query = Job::query()->with(['property', 'customer', 'crew', 'jobServices.service']);

        if ($employee->role !== 'estimator') {
            $query->whereIn('crew_id', $employee->crewIds());
        }

        $filter = (string) $request->query('filter', 'all');
        if (in_array($filter, ['scheduled', 'in_progress', 'completed'], true)) {
            $query->where('status', $filter);
        }

        $jobs = $query->orderByDesc('scheduled_date')->orderByDesc('id')->limit(200)->get();

        // Batch-translate the whole list once for non-English staff (issue #56).
        \App\Support\TranslationWarmer::jobs($jobs, \App\Support\AppLocale::target($request));

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

        // Spray Tech intelligence (issue #12): flag a mowing job scheduled within
        // two days of this spray job at the same property.
        $job->mowing_conflict = $this->mowingConflict($job);

        \App\Support\TranslationWarmer::jobs([$job], \App\Support\AppLocale::target($request));

        return new JobResource($job);
    }

    /**
     * GET /api/jobs/{job}/map — a static map thumbnail with a pin at the job's
     * property. Proxied through the server so the Google Maps key never reaches
     * the device. Returns the raw image bytes (404 when there's nothing to show).
     */
    public function map(Request $request, Job $job)
    {
        $this->authorizeView($request->user(), $job);

        $job->loadMissing('property:id,latitude,longitude');
        $property = $job->property;
        $key = config('services.google.maps_key');

        abort_if(
            ! $key || ! $property || $property->latitude === null || $property->longitude === null,
            404,
            'No map available for this job.',
        );

        $lat = (float) $property->latitude;
        $lng = (float) $property->longitude;

        $params = http_build_query([
            'center' => "{$lat},{$lng}",
            'zoom' => 16,
            'size' => '640x320',
            'scale' => 2,
            'maptype' => 'roadmap',
            'markers' => "color:0xe00a35|{$lat},{$lng}",
            'key' => $key,
        ]);

        $response = Http::timeout(8)->get("https://maps.googleapis.com/maps/api/staticmap?{$params}");

        abort_unless($response->successful(), 502, 'Map service unavailable.');

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type') ?: 'image/png',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    /**
     * If this job involves spraying, find a mowing job at the same property
     * scheduled within two days of it. Returns null when there's no conflict.
     *
     * @return array{scheduled_date: string, days_away: int, title: ?string}|null
     */
    private function mowingConflict(Job $job): ?array
    {
        if (! $job->property_id || ! $job->scheduled_date) {
            return null;
        }

        $isSpray = $job->jobServices
            ->contains(fn ($jobService) => $jobService->service?->service_group === 'spraying');

        if (! $isSpray) {
            return null;
        }

        $date = $job->scheduled_date->copy();

        $mowing = Job::query()
            ->where('property_id', $job->property_id)
            ->where('id', '!=', $job->id)
            ->whereNotIn('status', ['cancelled', 'skipped'])
            ->whereNotNull('scheduled_date')
            ->whereBetween('scheduled_date', [
                $date->copy()->subDays(2)->toDateString(),
                $date->copy()->addDays(2)->toDateString(),
            ])
            ->whereHas('jobServices.service', fn ($q) => $q->where('service_group', 'mowing'))
            ->orderByRaw('ABS(DATEDIFF(scheduled_date, ?))', [$date->toDateString()])
            ->first();

        if (! $mowing) {
            return null;
        }

        return [
            'scheduled_date' => $mowing->scheduled_date->toDateString(),
            'days_away' => (int) $date->diffInDays($mowing->scheduled_date, false),
            'title' => $mowing->title,
        ];
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
     * POST /api/jobs/{job}/services/{jobService}/toggle — check/uncheck a job
     * service as completed. Foreman / spray tech only.
     */
    public function toggleService(Request $request, Job $job, JobService $jobService): JsonResponse
    {
        $this->authorizeForeman($request->user(), $job);
        abort_unless($jobService->job_id === $job->id, 404);

        $jobService->update([
            'completed_at' => $jobService->completed_at ? null : now(),
        ]);

        return response()->json(['data' => [
            'id' => $jobService->id,
            'completed' => $jobService->completed_at !== null,
            'completed_at' => $jobService->completed_at?->toIso8601String(),
        ]]);
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
     * Only a foreman (or spray tech, who mirrors the foreman) of the job's crew
     * may run the job clock.
     */
    private function authorizeForeman(Employee $employee, Job $job): void
    {
        abort_unless(
            in_array($employee->role, ['foreman', 'spray_tech'], true)
                && $employee->crewIds()->contains($job->crew_id),
            403,
            'Only the crew foreman can start or complete this job.',
        );
    }
}
