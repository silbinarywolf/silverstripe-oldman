<?php

namespace Symbiote\Cloudflare;

class PurgeURLTask extends \SilverStripe\Dev\BuildTask
{
    use PurgeTask;

    protected $title = 'Cloudflare Purge: URL';

    protected $description = 'Purges a single or multiple URLs, with an absolute or relative URL (ie. url="admin/,Security/" or url="http://myproductionsite.com/admin, http://myproductionsite.com/Security")';

    protected $param_url = [];

    public function run($request)
    {
        $url = $request->getVar('purge_url');
        if (!$url) {
            $this->log('Missing "purge_url" parameter.');
            return;
        }

        // Allow multiple URLs
        $urlList = explode(',', (string) $url);
        foreach ($urlList as $i => $url) {
            $url = trim($url);
            // Remove URL if it's a blank string, this allows trailing commas
            if (!$url) {
                unset($urlList[$i]);
            }
        }
        $this->param_url = $urlList;

        return $this->endRun($request);
    }

    public function callPurgeFunction(Cloudflare $client)
    {
        return $client->purgeURLs($this->param_url);
    }
}
