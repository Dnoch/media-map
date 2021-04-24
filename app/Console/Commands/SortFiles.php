<?php

namespace App\Console\Commands;

use App\Models\Download;
use App\Models\Drive;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SortFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sort:files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    public $path = "";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->path = env('WATCH_FOLDER');
        $downloads = $this->get_watch_dir_files();
        $copied = 0;
        $deleted = 0;
        $seeding = 0;

        foreach ($downloads as $download) {
            if ($download->status_id == 1) {
                $this->copy_download ($download);
                $copied ++;
            }
            else if ($download->status_id == 2) {
                $date_cmp = Carbon::parse($download->created_at)->addDays(10);
                if ($date_cmp->lessThanOrEqualTo(Carbon::now())) {
                    $this->delete_download ($download);
                    $deleted ++;
                } else {
                    $seeding++;
                }
            }
        }

        $this->info($copied . " new files found and copied");
        $this->info($seeding . " files remain in watch folder to continue seeding");
        $this->info($deleted . ' files completed seeding and deleted from watch folder');
        return 0;
    }

    public function get_watch_dir_files ()
    {
        $root_folders = File::directories($this->path);
        $root_files = File::files($this->path);

        $downloads = [];
        foreach ($root_folders as $folder) {
            $item = explode('/', $folder);
            $item = $item[sizeof($item) - 1];
            $downloads[] = $this->get_or_save_download($item, 2);
        }

        foreach ($root_files as $file) {
            $item = $file->getRelativePathname();
            $downloads[] = $this->get_or_save_download($item, 1);
        }
        return $downloads;
    }

    public function get_or_save_download ($item, $type_id)
    {
        return Download::firstOrCreate(
            ['name' => $item, 'type_id' => $type_id],
            ['status_id' => 1]
        );
    }

    public function copy_download ($download)
    {
        $words = explode('.', $download->name[0]);
        $word = $words[0];
        if (strtolower($words[0]) == "the") {
            if (isset ($words[1])) {
                $word = $words[1];
            }
        }
        $letter = strtoupper($word);
        $drive = Drive::where('starting_letter', '<=', $letter)
            ->where('ending_letter', '>=', $letter)
            ->first();
        if (!isset ($drive)) {
            $drive = Drive::where('starting_letter', '<=', 'A')
                ->where('ending_letter', '>=', 'A')
                ->first();
        }
        if ($download->type_id == 1) {
            //file
            File::copy($this->path . '/' . $download->name, $drive->path . $download->name);
        } else {
            //directory
            File::copyDirectory($this->path . '/' . $download->name, $drive->path . $download->name);
        }
        $download->status_id = 2;
        $download->save();
    }

    public function delete_download ($download)
    {
        if ($download->type_id == 1) {
            //file
            File::delete($this->path . '/' . $download->name);
        } else {
            //directory
            File::deleteDirectory($this->path . '/' . $download->name);
        }
        $download->status_id = 3;
        $download->save();
    }
}
