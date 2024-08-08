<?php

namespace Symbiote\Cloudflare;

use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\File;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;
use Symbiote\Cloudflare\CloudflareResult;

class FileExtension extends DataExtension
{
    public function onAfterPublish()
    {
        if (!Cloudflare::config()->enabled) {
            return;
        }
        $cloudflareResult = Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgeURLs([$this->owner->AbsoluteLink()]);
        $this->addInformationToHeader($cloudflareResult);
    }

    public function onAfterUnpublish()
    {
        if (!Cloudflare::config()->enabled) {
            return;
        }
        $cloudflareResult = Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgeURLs([$this->owner->AbsoluteLink()]);
        $this->addInformationToHeader($cloudflareResult);
    }

    private function addInformationToHeader(CloudflareResult $cloudflareResult = null)
    {
        if (!Controller::has_curr()) {
            return false;
        }
        if (!$cloudflareResult) {
            return false;
        }
        $controller = Controller::curr();
        if (!($controller instanceof AssetAdmin)) {
            return false;
        }
        $result = false;
        $urls = $cloudflareResult->getSuccesses();
        $errors = $cloudflareResult->getErrors();
        $response = Controller::curr()->getResponse();
        if ($urls) {
            $response->addHeader('oldman-cloudflare-cleared-links', implode(",", $urls));
            $result = true;
        }
        if ($errors) {
            $response->addHeader('oldman-cloudflare-errors', implode(",", $errors));
            $result = true;
        }
        return $result;
    }
}
