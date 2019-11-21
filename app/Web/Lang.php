<?php

/*
 * @copyright Copyright (c) 2019 Sergio Brighenti <sergio@brighenti.me>
 *
 * @author Sergio Brighenti <sergio@brighenti.me>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

namespace App\Web;

class Lang
{
    const DEFAULT_LANG = 'en';
    const LANG_PATH = __DIR__.'../../resources/lang/';

    /** @var string */
    protected static $langPath = self::LANG_PATH;

    /** @var string */
    protected static $lang;

    /** @var Lang */
    protected static $instance;

    /** @var array */
    protected $cache = [];

    /**
     * @return Lang
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $lang
     * @param string $langPath
     *
     * @return Lang
     */
    public static function build($lang = self::DEFAULT_LANG, $langPath = null): self
    {
        self::$lang = $lang;

        if ($langPath !== null) {
            self::$langPath = $langPath;
        }

        self::$instance = new self();

        return self::$instance;
    }

    /**
     * Recognize the current language from the request.
     *
     * @return bool|string
     */
    public static function recognize()
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        }

        return self::DEFAULT_LANG;
    }

    /**
     * @return string
     */
    public static function getLang(): string
    {
        return self::$lang;
    }

    /**
     * @param $lang
     */
    public static function setLang($lang)
    {
        self::$lang = $lang;
    }

    /**
     * @return array
     */
    public static function getList()
    {
        $languages = [];

        $default = count(include self::$langPath.self::DEFAULT_LANG.'.lang.php') - 1;

        foreach (glob(self::$langPath.'*.lang.php') as $file) {
            $dict = include $file;

            if (!is_array($dict)) {
                continue;
            }

            $count = count($dict) - 1;
            $percent = min(round(($count / $default) * 100), 100);

            $languages[str_replace('.lang.php', '', basename($file))] = "[{$percent}%] ".$dict['lang'];
        }

        return $languages;
    }

    /**
     * @param $key
     * @param array $args
     *
     * @return string
     */
    public function get($key, $args = []): string
    {
        return $this->getString($key, self::$lang, $args);
    }

    /**
     * @param $key
     * @param $lang
     * @param $args
     *
     * @return string
     */
    private function getString($key, $lang, $args): string
    {
        $redLang = strtolower(substr($lang, 0, 2));

        if (array_key_exists($lang, $this->cache)) {
            $transDict = $this->cache[$lang];
        } else {
            if (file_exists(self::$langPath.$lang.'.lang.php')) {
                $transDict = include self::$langPath.$lang.'.lang.php';
                $this->cache[$lang] = $transDict;
            } else {
                if (file_exists(self::$langPath.$redLang.'.lang.php')) {
                    $transDict = include self::$langPath.$redLang.'.lang.php';
                    $this->cache[$lang] = $transDict;
                } else {
                    $transDict = [];
                }
            }
        }

        if (array_key_exists($key, $transDict)) {
            return vsprintf($transDict[$key], $args);
        }

        if ($lang !== self::DEFAULT_LANG) {
            return $this->getString($key, self::DEFAULT_LANG, $args);
        }

        return $key;
    }
}
