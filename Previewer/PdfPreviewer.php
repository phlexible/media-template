<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Component\MediaTemplate\Previewer;

use Monolog\Handler\TestHandler;
use Phlexible\Component\MediaTemplate\Applier\PdfTemplateApplier;
use Phlexible\Component\MediaTemplate\Model\PdfTemplate;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;

/**
 * PDF previewer
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class PdfPreviewer implements PreviewerInterface
{
    /**
     * @var PdfTemplateApplier
     */
    private $applier;

    /**
     * @var FileLocator
     */
    private $locator;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @param PdfTemplateApplier $applier
     * @param FileLocator        $locator
     * @param string             $cacheDir
     */
    public function __construct(PdfTemplateApplier $applier, FileLocator $locator, $cacheDir)
    {
        $this->applier = $applier;
        $this->locator = $locator;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return string
     */
    public function getPreviewDir()
    {
        return $this->cacheDir;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function create(array $params)
    {
        $filePath = $this->locator->locate('@PhlexibleMediaTemplateBundle/Resources/public/pdf/test.pdf', null, true);

        $filesystem = new Filesystem();
        if (!$filesystem->exists($this->cacheDir)) {
            $filesystem->mkdir($this->cacheDir);
        }

        $template = new PdfTemplate();
        $templateKey = 'unknown';
        $debug = false;
        foreach ($params as $key => $value) {
            if ($key === 'template') {
                $templateKey = $value;
                continue;
            } elseif ($key === '_dc') {
                continue;
            } elseif ($key === 'debug') {
                $debug = true;
                continue;
            }

            $template->setParameter($key, $value);
        }

        $template->setParameter('simpleviewer_enable', 1);

        if ($debug) {
            $logger = $this->applier->getLogger();
            $logger->pushHandler(new TestHandler());
        }

        $extension = $this->applier->getExtension($template);
        $cacheFilename = $this->cacheDir . 'preview_pdf.' . $extension;
        $this->applier->apply($template, $filePath, $cacheFilename);

        if ($debug) {
            /* @var $logger \Monolog\Logger */
            $handler = $this->applier->getLogger()->popHandler();

            $debug = '';
            foreach ($handler->getRecords() as $record) {
                $debug .= $record['message'] . PHP_EOL;
            }
        } else {
            $debug = '';
        }

        $data = [
            'file' => basename($cacheFilename),
            'size' => filesize($cacheFilename),
            'template' => $templateKey,
            'format' => $extension,
            'mimetype' => $this->applier->getMimetype($template),
            'debug' => $debug,
        ];

        return $data;
    }
}