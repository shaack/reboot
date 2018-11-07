<?php
/**
 * Author and copyright: Stefan Haack (https://shaack.com)
 * Repository: https://github.com/shaack/reboot
 * License: MIT, see file 'LICENSE'
 */

class Page
{
    private $reboot;
    private $article;

    /**
     * Page constructor.
     * @param \Shaack\Reboot\Reboot $reboot
     * @param \Shaack\Reboot\Article $article
     */
    public function __construct($reboot, $article)
    {
        $this->reboot = $reboot;
        $this->article = $article;
    }

    /**
     * @param string $template
     * @return string
     */
    public function render($template = "default")
    {
        // render template
        ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->reboot->baseDir . '/local/templates/' . $template . ".php";
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
}