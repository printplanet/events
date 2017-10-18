<?php

namespace PP\Component\Events;

use PP\Component\Queue\Job\JobsInterface;
use PP\Component\Container\Container;
use PP\Component\Queue\Job\InteractsWithQueueJob;
use PP\Component\Queue\ShouldQueue;

class CallQueuedListener extends InteractsWithQueueJob
{

    /**
     * The listener class name.
     *
     * @var string
     */
    public $class;

    /**
     * The listener method.
     *
     * @var string
     */
    public $method;

    /**
     * The data to be passed to the listener.
     *
     * @var array
     */
    public $data;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The timestamp indicating when the job should timeout.
     *
     * @var int
     */
    public $timeoutAt;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout;

    /**
     * Create a new job instance.
     *
     * @param  string  $class
     * @param  string  $method
     * @param  array  $data
     */
    public function __construct($class, $method, $data)
    {
        $this->data = $data;
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Handle the queued job.
     *
     * @param  Container  $container
     * @return void
     */
    public function handle(Container $container)
    {
        $this->prepareData();

        $handler = $this->setJobInstanceIfNecessary(
            $this->job, $container->make($this->class)
        );

        call_user_func_array(
            array($handler, $this->method), $this->data
        );
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * @param  JobsInterface  $job
     * @param  mixed  $instance
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(JobsInterface $job, $instance)
    {

        if ($instance instanceof ShouldQueue) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Call the failed method on the job instance.
     *
     * The event instance and the exception will be passed.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function failed($e)
    {
        $this->prepareData();

        $handler = Container::getInstance()->make($this->class);

        $parameters = array_merge($this->data, array($e));

        if (method_exists($handler, 'failed')) {
            call_user_func_array(array($handler, 'failed'), $parameters);
        }
    }

    /**
     * Unserialize the data if needed.
     *
     * @return void
     */
    protected function prepareData()
    {
        if (is_string($this->data)) {
            $this->data = unserialize($this->data);
        }
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName()
    {
        return $this->class;
    }

    /**
     * Prepare the instance for cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->data = array_map(function ($data) {
            return is_object($data) ? clone $data : $data;
        }, $this->data);
    }
}