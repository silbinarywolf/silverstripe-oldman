<?php

namespace Symbiote\Cloudflare\Tests;

use ReflectionObject;
use SiteTree;
use Injector;
use Requirements;
use FunctionalTest;
use Symbiote\Cloudflare\Cloudflare;

class CloudflareTest extends FunctionalTest
{
    const ASSETS_DIR_RELATIVE = 'oldman/tests/assets';

    protected static $disable_themes = true;

    /**
     * Effectively a test stub.
     */
    public function testPurgePageFailure()
    {
        $page = new SiteTree();

        $result = Injector::inst()->get(Cloudflare::CloudflareClass)->purgePage($page);
        // Expects `null` when not configured.
        $this->assertNull($result);
    }

    /**
     * Effectively a test stub.
     */
    public function testPurgeCSSAndJS()
    {
        // Generate combined files
        Requirements::combine_files(
            'combined.min.css', array(
            self::ASSETS_DIR_RELATIVE.'/test_combined_css_a.css',
            self::ASSETS_DIR_RELATIVE.'/test_combined_css_b.css',
            )
        );
        Requirements::process_combined_files();

        $files = $this->getFilesToPurgeByExtensions(
            array(
            'css',
            'js',
            'json',
            )
        );
        $this->assertNull(1, print_r($files, true));
        exit('exiting here!');
    }

    /**
     * Wrapper to expose private method 'getFilesToPurgeByExtensions'
     *
     * @return array
     */
    protected function getFilesToPurgeByExtensions(array $fileExtensions)
    {
        $service = Injector::inst()->get(Cloudflare::CloudflareClass);
        $reflector = new ReflectionObject($service);
        $method = $reflector->getMethod('getFilesToPurgeByExtensions');
        $method->setAccessible(true);
        // NOTE(Jake): 2018-04-18
        //
        // We skip "File::get()" calls with the $skipDatabaseRecords parameter.
        // This is to make executing tests faster.
        //
        $skipDatabaseRecords = true;
        $results = $method->invoke($service, $fileExtensions, $skipDatabaseRecords);
        // NOTE(Jake): 2018-04-18
        //
        // Searching through a directory recursively will have files unordered.
        // We sort in tests so that datasets are more predictable.
        //
        sort($results);
        return $results;
    }
}
