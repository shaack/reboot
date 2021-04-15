<?php
/**
 * Author and copyright: Stefan Haack (https://shaack.com)
 * Repository: https://github.com/shaack/reboot-cms
 * License: MIT, see file 'LICENSE'
 */

namespace Shaack\Reboot;

use DOMDocument;
use Shaack\Utils\Logger;

class Block
{
    private $name;
    private $content;
    private $xpath;
    private $config;
    private $reboot;
    private $page;

    private static $parsedown;

    /**
     * @param Reboot $reboot
     * @param Page $page
     * @param string $name
     * @param string $content
     * @param array $config
     */
    public function __construct(Reboot $reboot, Page $page, string $name, string $content = "", array $config = [])
    {
        if (!$this::$parsedown) {
            $this::$parsedown = new \Parsedown();
        }
        $this->name = $name;
        $this->content = $content;
        $this->config = $config;
        $this->reboot = $reboot;
        $this->page = $page;

        $html = $this::$parsedown->parse($this->content);
        $document = new DOMDocument();
        $document->loadHTML($html);
        $this->xpath = new \DOMXPath($document);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        Logger::debug("");
        Logger::debug("Rendering Block " . $this->name);
        Logger::debug($this->xpath->document->saveHTML());
        return renderBlock($this->reboot, $this->page, $this);
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Queries a value or a part in the markdown to use it in the block template
     * @param string $expression
     * @return string
     */
    public function xpath(string $expression): string
    {
        Logger::debug("query: " . $expression);
        // replace part(n), https://stackoverflow.com/questions/10859703/xpath-select-all-elements-between-two-specific-elements
        $expression = preg_replace_callback("/part\((\d)\)/", function ($matches) {
            $partNumber = $matches[1] - 1;
            return "count(preceding::hr)=$partNumber and not(self::hr)";
        }, $expression);
        $expression = "/html/body" . $expression;
        $result = $this->xpath->query($expression);
        $ret = "";
        if ($result === false) {
            Logger::error("xquery error");
            $ret = "*xquery error*";
        } else {
            if ($result->length === 1) {
                $result = $result->item(0);
            }
            if ($result instanceof \DOMText or $result instanceof \DOMAttr) {
                $ret = $result->nodeValue;
            } else if ($result instanceof \DOMElement) {
                $ret = $this->xpath->document->saveHTML($result);
            } else if ($result instanceof \DOMNodeList) {
                if ($result->length === 0) {
                    $ret = "<!-- no result for expression: " . $expression . " -->";
                } else {
                    $temp_dom = new DOMDocument();
                    foreach ($result as $node) {
                        $temp_dom->appendChild($temp_dom->importNode($node, true));
                    }
                    $ret = $temp_dom->saveHTML();
                }
            } else {
                Logger::error("(72a2) Unknown query result " . get_class($result));
            }
            Logger::debug($expression . " => " . $ret);
        }
        return $ret;
    }

    /**
     * Return all pages parsed to HTML
     * @return string
     */
    public function content(): string
    {
        if ($this->content) {
            return $this::$parsedown->parse($this->content);
        } else {
            return "";
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}

/** @noinspection PhpUnusedParameterInspection */
function renderBlock(Reboot $reboot, Page $page, Block $block) {
    ob_start();
    /** @noinspection PhpIncludeInspection */
    include $reboot->getBaseDir() . '/themes/' . $reboot->getConfig()['theme'] . '/blocks/' . $block->getName() . ".php";
    $contents = ob_get_contents();
    ob_end_clean();
    return $contents;
}
