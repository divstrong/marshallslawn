<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\TagCategory;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportTags extends Command
{
    protected $signature = 'tags:import
                            {--path=tags.csv : CSV path relative to the app base directory}
                            {--fresh : Wipe existing tags + categories before importing}';

    protected $description = 'Import the tag dictionary (tag name + parent category) from a ServiceNow tags.csv export';

    public function handle(): int
    {
        $path = base_path($this->option('path'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            \DB::table('taggables')->delete();
            Tag::query()->delete();
            TagCategory::query()->delete();
            $this->warn('Cleared existing tags, categories, and assignments.');
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle); // header: Tag, Category, Automation Tag, Modified

        $categoryIds = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $name = trim((string) ($row[0] ?? ''));
            if ($name === '') {
                $skipped++;
                continue;
            }

            $categoryName = trim((string) ($row[1] ?? ''));
            $categoryId = null;
            if ($categoryName !== '') {
                $categoryId = $categoryIds[$categoryName]
                    ??= TagCategory::firstOrCreate(['name' => $categoryName])->id;
            }

            $isAutomation = stripos(trim((string) ($row[2] ?? '')), 'check') !== false;

            $modifiedAt = null;
            $rawDate = trim((string) ($row[3] ?? ''));
            if ($rawDate !== '') {
                try {
                    $modifiedAt = Carbon::parse($rawDate)->toDateString();
                } catch (\Throwable $e) {
                    // leave null on unparseable dates
                }
            }

            $tag = Tag::updateOrCreate(
                ['name' => $name],
                [
                    'tag_category_id' => $categoryId,
                    'is_automation' => $isAutomation,
                    'source_modified_at' => $modifiedAt,
                ],
            );

            $tag->wasRecentlyCreated ? $created++ : $updated++;
        }

        fclose($handle);

        $this->info("✓ Tags imported — {$created} created, {$updated} updated, {$skipped} skipped.");
        $this->info('✓ Categories: ' . TagCategory::count() . ' (' . TagCategory::orderBy('name')->pluck('name')->implode(', ') . ')');

        return self::SUCCESS;
    }
}
