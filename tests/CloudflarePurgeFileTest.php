<?php

namespace Symbiote\Cloudflare\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\View\Requirements;
use Symbiote\Cloudflare\Cloudflare;
use ReflectionObject;

class CloudflarePurgeFileTest extends FunctionalTest
{
    /**
     * The assets used by the tests
     */
    public const ASSETS_DIR = 'vendor/symbiote/silverstripe-oldman/tests/assets';

    /**
     * This is used to determine if the 'framework' folder was scanned
     * for CSS/JS files.
     */
    public const FRAMEWORK_CSS_FILE = 'vendor/silverstripe/framework/client/styles/debug.css';

    protected static $disable_themes = true;

    /**
     * This tests if we get the correct files from a project when
     * purging CSS and JS.
     *
     * This means that CSS/JS files within "framework", "vendor" and other
     * folders should be ignored.
     */
    public function testPurgeCSSAndJS()
    {
        // Create files
        @mkdir(self::ASSETS_DIR, 0777, true);
        file_put_contents(self::ASSETS_DIR.'/test_combined_css_a.css', '.selector_a { width: 100%; }');
        file_put_contents(self::ASSETS_DIR.'/test_combined_css_b.css', '.selector_b { width: 100%; }');

        // Generate combined files
        Requirements::delete_all_combined_files();
        Requirements::set_combined_files_enabled(true); // not enabled by default in SS4
        Requirements::combine_files(
            'combined.min.css',
            [self::ASSETS_DIR.'/test_combined_css_a.css', self::ASSETS_DIR.'/test_combined_css_b.css']
        );
        Requirements::process_combined_files();

        //
        $files = $this->getFilesToPurgeByExtensions(
            ['css', 'js', 'json']
        );
        $expectedFiles = [
            // NOTE(Jake): 2018-04-19
            //
            // In SS4, combined files have a partial-hash
            // ie. assets/_combinedfiles/combined.min-1a933ce.css
            //
            // So we only partially match the name.
            //
            ASSETS_DIR.'/_combinedfiles/combined.min-',
        ];
        // Search for matches
        $matchCount = 0;
        foreach ($files as $file) {
            foreach ($expectedFiles as $expectedFile) {
                if (str_contains((string) $file, $expectedFile)) {
                    $matchCount++;
                    break;
                }
            }
        }
        $this->assertEquals(
            count($expectedFiles),
            $matchCount,
            "Expected file list:\n".print_r($expectedFiles, true)."Instead got:\n".print_r($files, true)
        );

        // If it has a file from the 'framework' module, fail this test as it should be ignored.
        $hasFramework = false;
        foreach ($files as $file) {
            $hasFramework = $hasFramework || (str_contains((string) $file, self::FRAMEWORK_CSS_FILE));
        }
        $this->assertFalse($hasFramework, 'Expected to specifically not get the "framework" file: '.self::FRAMEWORK_CSS_FILE);

        // Cleanup
        //@rmdir(self::ASSETS_DIR);
    }

    /**
     * Test if this can detect the CSS file in framework when the default blacklist is disabled.
     */
    public function testAllowBlacklistedDirectories()
    {
        Config::inst()->set(Cloudflare::FILESYSTEM_CLASS, 'disable_default_blacklist_absolute_pathnames', true);
        $files = $this->getFilesToPurgeByExtensions(
            ['css', 'js', 'json']
        );
        Config::inst()->set(Cloudflare::FILESYSTEM_CLASS, 'disable_default_blacklist_absolute_pathnames', false);

        // If it has a file from the 'framework' module, fail this test as it should be ignored.
        $hasFramework = false;
        foreach ($files as $file) {
            $hasFramework = $hasFramework || (str_contains((string) $file, self::FRAMEWORK_CSS_FILE));
        }
        $this->assertTrue(
            $hasFramework,
            'Expected to get "framework" file: '.self::FRAMEWORK_CSS_FILE."\nInstead got:".print_r($files, true)
        );
    }

    /**
     * Wrapper to expose private method 'getFilesToPurgeByExtensions'
     *
     * @return array
     */
    private function getFilesToPurgeByExtensions(array $fileExtensions)
    {
        $service = Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS);
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
