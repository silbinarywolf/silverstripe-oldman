<?php

namespace Symbiote\Cloudflare;

class CloudflareResult
{
    /**
     * @var array
     */
    protected $successes = [];

    /**
     * @var array
     */
    protected $errors = [];

    public function __construct(array $files, array $errorRecords)
    {
        // Apply to this object
        if (empty($errorRecords)) {
            $this->successes = $files;
        }
        $this->errors = $errorRecords;
    }

    /**
     * @var array
     */
    public function getSuccesses()
    {
        return $this->successes;
    }

    /**
     * @var array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
