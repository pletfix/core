<?php

namespace Core\Services\Contracts;

interface Translator
{
    /**
     * Get the translation for the given key.
     *
     * If the key does not exist, the key is returned.
     *
     * @param string $key Key using "dot" notation.
     * @param mixed $replace Values replacing the placeholders.
     * @param bool $fallback
     * @return string|array
     */
    public function translate($key, array $replace = [], $fallback = true);

    /**
     * Determine if a translation exists either for the actual locale or fallback locale.
     *
     * @param string $key Key using "dot" notation.
     * @return bool
     */
    public function has($key);

    /**
     * Determine if a translation exists for the locale.
     *
     * @param  string $key Key using "dot" notation.
     * @return bool
     */
    public function hasForLocale($key);

    /**
     * Get the locale.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set the locale.
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale);
}
