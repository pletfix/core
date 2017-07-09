<?php

namespace Core\Services;

use Core\Services\Contracts\Translator as TranslatorContract;

class Translator implements TranslatorContract
{
    /**
     * The language entries.
     *
     * @var array
     */
    private $entries = [];

    /**
     * The locale.
     *
     * @var string
     */
    private $locale;

    /**
     * The fallback locale.
     *
     * @var string
     */
    private $fallback;

    /**
     * Plugin's language files
     *
     * @var array
     */
    private static $manifest;

    /**
     * Manifest file of languages.
     *
     * @var string
     */
    private $pluginManifestOfLanguages;

    /**
     * Create a new Translator instance.
     * @param string|null $pluginManifestOfLanguages
     */
    public function __construct($pluginManifestOfLanguages = null)
    {
        $this->pluginManifestOfLanguages = $pluginManifestOfLanguages ?: manifest_path('plugins/languages.php');
    }

    /**
     * @inheritdoc
     */
    public function translate($key, array $replace = [], $fallback = true)
    {
        $keys = explode('.', $key);
        $dictionary = array_shift($keys);

        // find the translation for the locale
        $translation = $this->find($this->getLocale(), $dictionary, $keys);

        // if no translation exist for the local, try the fallback...
        if ($translation === null && $fallback) {
            if ($this->fallback === null) {
                $this->fallback = config('app.fallback_locale');
            }
            if ($this->fallback != $this->locale) {
                $translation = $this->find($this->fallback, $dictionary, $keys);
            }
        }

        // if the translation is not present, return the key
        if ($translation === null || is_array($translation)) {
            return $key;
        }

        // replace placeholders
        if (!empty($replace)) {
            $replacements = [];
            foreach ($replace as $name => $value) {
                $replacements['{' . $name . '}'] = $value;
            }
            $translation = strtr($translation, $replacements);
        }

        return $translation;
    }

    /**
     * Find the translation.
     *
     * @param string $locale
     * @param string $dictionary
     * @param array $keys
     * @return string|array|null
     */
    private function find($locale, $dictionary, $keys)
    {
        if (!isset($this->entries[$locale][$dictionary])) {
            if (!isset($this->entries[$locale])) {
                $this->entries[$locale] = [];
            }
            $file = $this->dictionaryFile($dictionary, $locale);
            /** @noinspection PhpIncludeInspection */
            $this->entries[$locale][$dictionary] = $file !== null && @file_exists($file) ? include $file : [];
        }

        $entries = $this->entries[$locale][$dictionary];
        foreach ($keys as $key) {
            if (!isset($entries[$key])) {
                return null;
            }
            $entries = $entries[$key];
        }

        return $entries;
    }

    /**
     * Get the full filename of the dictionary.
     *
     * @param string $dictionary
     * @param string $locale
     * @return string
     */
    private function dictionaryFile($dictionary, $locale)
    {
        $file = resource_path('lang/' . $locale . '/' . $dictionary . '.php');
        if (@file_exists($file)) {
            return $file;
        }

        if (self::$manifest === null) {
            if (@file_exists($this->pluginManifestOfLanguages)) {
                /** @noinspection PhpIncludeInspection */
                self::$manifest = include $this->pluginManifestOfLanguages;
            }
        }

        return isset(self::$manifest[$locale][$dictionary]) ? base_path(self::$manifest[$locale][$dictionary]) : null;
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return $this->translate($key) !== $key;
    }

    /**
     * @inheritdoc
     */
    public function hasForLocale($key)
    {
        return $this->translate($key, [], false) !== $key;
    }

    /**
     * @inheritdoc
     */
    public function getLocale()
    {
        if ($this->locale === null) {
            $this->locale = config('app.locale');
        }

        return $this->locale;
    }

    /**
     * @inheritdoc
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }
}