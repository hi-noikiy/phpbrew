<?php

namespace PhpBrew\Extension\Provider;

use PhpBrew\Config;

class BitbucketProvider implements Provider {

    public $site = 'bitbucket.org';
    public $owner = NULL;
    public $repository = NULL;
    public $packageName = NULL;
    public $defaultVersion = 'master';

    public static function getName() {
        return 'bitbucket';
    }

    public function buildPackageDownloadUrl($version='stable')
    {
        if (($this->getOwner() == NULL) || ($this->getRepository() == NULL)) {
            throw new Exception("Username or Repository invalid.");
        }
        return sprintf('https://%s/%s/%s/get/%s.tar.gz', $this->site, $this->getOwner(), $this->getRepository(), $version);
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    public function getPackageName()
    {
        return $this->packageName;
    }

    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;
    }

    public function exists($url, $packageName = NULL)
    {
        $matches = array();

        // check url scheme is bitbucket:owner/repos and convert to https
        if (preg_match("#bitbucket:([0-9a-zA-Z-._]*)/([0-9a-zA-Z-._]*)#", $url, $matches)) {
            $url = sprintf("https://bitbucket.org/%s/%s", $matches[1], $matches[2]);
        }

        // check url scheme is git@github.com and convert to https
        if (preg_match("#git@bitbucket.org:([0-9a-zA-Z-._]*)/([0-9a-zA-Z-._]*).git#", $url, $matches)) {
            $url = sprintf("https://bitbucket.org/%s/%s", $matches[1], $matches[2]);
        }

        // parse owner and repository
        if (preg_match("#https://bitbucket.org/([0-9a-zA-Z-._]*)/([0-9a-zA-Z-._]*)#", $url, $matches)) {
            $this->setOwner($matches[1]);
            $this->setRepository($matches[2]);
            if ($packageName == NULL) $packageName = $matches[2];
            $this->setPackageName($packageName);
            return true;
        }else {
            $this->setOwner(NULL);
            $this->setRepository(NULL);
            $this->setPackageName(NULL);
            return false;
        }
    }

    public function buildKnownReleasesUrl()
    {
        return sprintf("https://bitbucket.org/api/1.0/repositories/%s/%s/tags/", $this->getOwner(), $this->getRepository());
    }

    public function parseKnownReleasesResponse($content)
    {
        $info = json_decode($content, TRUE);
        $versionList = array_keys($info);

        return $versionList;
    }

    public function getDefaultVersion()
    {
        return $this->defaultVersion;
    }

    public function setDefaultVersion($version)
    {
        $this->defaultVersion = $version;
    }

    public function shouldLookupRecursive()
    {
        return true;
    }

    public function resolveDownloadFileName($version)
    {
        return sprintf("%s-%s-%s.tar.gz", $this->getOwner(), $this->getRepository(), $version);
    }

    public function extractPackageCommands($currentPhpExtensionDirectory, $targetFilePath)
    {
        $cmds = array(
            "tar -C $currentPhpExtensionDirectory -xzf $targetFilePath"
        );
        return $cmds;
    }

    public function postExtractPackageCommands($currentPhpExtensionDirectory, $targetFilePath)
    {
        $targetPkgDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $this->getPackageName();
        $extractDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $this->getOwner() . '-' . $this->getRepository() . '-*';

        $cmds = array(
            "rm -rf $targetPkgDir",
            "mv $extractDir $targetPkgDir"
        );
        return $cmds;
    }

} 