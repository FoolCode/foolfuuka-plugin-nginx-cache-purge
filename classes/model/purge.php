<?php

namespace Foolz\Foolfuuka\Plugins\NginxCachePurge\Model;

use Foolz\Foolframe\Model\Context;
use Foolz\Foolframe\Model\Model;
use Foolz\Foolframe\Model\Preferences;
use Foolz\Foolfuuka\Model\Media;

class NginxCachePurge extends Model
{
    /**
     * @var Preferences
     */
    protected $preferences;

    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->preferences = $context->getService('preferences');
    }

    public function beforeDeleteMedia($result)
    {
        /** @var Media $post */
        $post = $result->getObject();
        $file = [];

        try {
            $file['image'] = $post->getDir(false, true, true);
        } catch (\Foolz\Foolfuuka\Model\MediaException $e) {

        }

        try {
            $post->op = 0;
            $dir['thumb-0'] = $post->getDir(true, true, true);
        } catch (\Foolz\Foolfuuka\Model\MediaException $e) {

        }

        try {
            $post->op = 1;
            $dir['thumb-1'] = $post->getDir(true, true, true);
        } catch (\Foolz\Foolfuuka\Model\MediaException $e) {

        }

        foreach ($this->getUrls() as $instance) {
            foreach ($file as $uri) {
                if (null === $uri) {
                    continue;
                }

                $curl = curl_init();
                $opts = [
                    CURLOPT_URL => $instance['path'].$uri,
                    CURLOPT_RETURNTRANSFER => true
                ];

                if (isset($instance['pass'])) {
                    $opts = $opts + [
                        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                        CURLOPT_USERPWD => $instance['user'].':'.$instance['pass']
                    ];
                }

                curl_setopt_array($curl, $opts);
                curl_exec($curl);
                curl_close($curl);
            }
        }

        return null;
    }

    public function getUrls()
    {
        $text = $this->preferences->get('foolfuuka.plugins.nginx_cache_purge.urls');
        $data = [];

        if ($text) {
            $lines = preg_split('/\r\n|\r|\n/', $text);

            foreach($lines as $key => $line) {
                $explode = explode('::', $line);

                if (count($explode) == 0) {
                    continue;
                }

                if (count($explode) >= 1) {
                    $data[$key]['path'] = rtrim(array_shift($explode), '/');
                }

                if (count($explode) >= 1) {
                    $data[$key]['user'] = array_shift($explode);
                    $data[$key]['pass'] = array_shift($explode);
                }
            }
        }

        return $data;
    }
}
