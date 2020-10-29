<?php

namespace NikRolls\SSHashPathManifest;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Path;
use SilverStripe\View\TemplateGlobalProvider;

class HashPath implements TemplateGlobalProvider
{
  use Injectable;
  use Configurable;

  private static $manifests = [];

  public static function HashPath($path)
  {
    return static::singleton()->for($path);
  }

  public static function get_template_global_variables()
  {
    return [
      'HashPath' => 'hashPath'
    ];
  }

  public function for($path)
  {
    $manifest = $this->getManifestFor($path);
    if ($manifest) {
      $relativePath = substr($path, strlen($manifest->pathPrefix));
      return Path::join($manifest->pathPrefix, $manifest->map[$relativePath]);
    } else {
      return $path;
    }
  }

  private function getManifestFor($path)
  {
    $manifestDefinition = $this->findManifestPathFor($path);
    if ($manifestDefinition) {
      $manifest = file_get_contents($manifestDefinition->path);
      return (object)[
        'pathPrefix' => $manifestDefinition->prefix,
        'map' => json_decode($manifest, true)
      ];
    } else {
      return null;
    }
  }

  private function findManifestPathFor($path)
  {
    $allManifests = $this->config()->get('manifests');
    $matchingManifestKeys = array_filter(
      array_keys($allManifests),
      function ($key) use ($path) {
        return strpos($path, $key) === 0;
      }
    );
    $matchingManifestKey = reset($matchingManifestKeys);

    return (object)[
      'prefix' => $matchingManifestKey . '/',
      'path' => Director::getAbsFile(Path::join($matchingManifestKey, $allManifests[$matchingManifestKey]))
    ];
  }
}
