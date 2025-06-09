<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Model;

use Closure;
use ErrorException;
use Psancho\Galeizon\Adapter\LogAdapter;
use Psancho\Galeizon\Model\FireAndForget\Job;
use Psancho\Galeizon\Pattern\Singleton;

class FireAndForget extends Singleton
{
    /** @var list<Job> */
    protected array $jobs = [];

    #[\Override]
    protected function build(): void
    {}

    function hasJobs(): bool
    {
        return count($this->jobs) > 0;
    }

    /** @param list<mixed> $args */
    public function addJob(Closure $closure, array $args = []): self// @phpstan-ignore missingType.callable
    {
        $this->jobs[] = new Job($closure, $args);

        return $this;
    }

    public function run(): void
    {
        ignore_user_abort(true);
        if (php_sapi_name() === "fpm-fcgi") {
            fastcgi_finish_request();
        }
        // Clean up buffers
        if (ob_get_level() > 0) {
            ob_end_clean();
            try {
                ob_flush();
            } catch (ErrorException) {} // @phpstan-ignore catch.neverThrown
        }
        flush();

        LogAdapter::debug("FnF: starting jobs");
        foreach ($this->jobs as $job) {
            ($job->closure)(...$job->args);
        }
        LogAdapter::debug("FnF: jobs complete");
    }
}
