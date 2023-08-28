<?php

namespace LaraWelP\SageThemeBlocks\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class PatchBud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patch-bud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs a patch for Bud to allow the client url to be configured.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $patchPath = app()->basePath() . '/vendor/larawelp/sage-theme-blocks/js/bud-client.patch';
        $runIn = app()->basePath();

        $process = Process::fromShellCommandline("git apply $patchPath -p1", $runIn);
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        echo $process->getOutput();

        if(!$process->isSuccessful()) {
            $this->error('Patch failed to apply. Maybe it was already applied? Or git not installed?');
            return Command::FAILURE;
        }

        $this->info('Patch applied successfully.');

        return Command::SUCCESS;
    }
}
