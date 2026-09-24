<?php

namespace Vitebox\LaravelBlog\Console;

use Illuminate\Console\Command;
use Vitebox\LaravelBlog\Services\PostWorkflow;

class PublishScheduledCommand extends Command
{
    protected $signature = 'blog:publish-scheduled';

    protected $description = 'Publish blog posts whose scheduled time has arrived';

    public function handle(PostWorkflow $workflow): int
    {
        $count = $workflow->publishDue();

        if ($count > 0 || $this->output->isVerbose()) {
            $this->components->info("Published {$count} scheduled post(s).");
        }

        return self::SUCCESS;
    }
}
