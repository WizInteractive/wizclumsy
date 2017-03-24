<?php

namespace Wizclumsy\CMS\Console;

use Illuminate\Console\Command;

/**
 * Publish the Clumsy assets to the public directory
 *
 * @author Tomas Buteler <tbuteler@gmail.com>
 */
class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clumsy:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Clumsy and dependencies\' assets';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $packages = [
            'Clumsy Utils'  => 'Wizclumsy\Utils\UtilsServiceProvider',
            'Clumsy Eminem' => 'Wizclumsy\Eminem\EminemServiceProvider',
            'Clumsy CMS'    => 'Wizclumsy\CMS\CMSServiceProvider',
        ];

        foreach ($packages as $title => $provider) {
            $this->callSilent('vendor:publish', ['--provider' => $provider, '--tag' => ['public'], '--force' => true]);
            $this->info("{$title} assets published");
        }
    }
}
