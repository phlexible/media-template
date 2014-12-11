<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Component\MediaTemplate\File;

use Phlexible\Bundle\GuiBundle\Locator\PatternResourceLocator;
use Phlexible\Component\MediaTemplate\File\Loader\LoaderInterface;
use Phlexible\Component\MediaTemplate\Model\TemplateCollection;

/**
 * Template loader
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class TemplateLoader
{
    /**
     * @var PatternResourceLocator
     */
    private $locator;

    /**
     * @var LoaderInterface[]
     */
    private $loaders = [];

    /**
     * @param PatternResourceLocator $locator
     */
    public function __construct(PatternResourceLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * @param LoaderInterface $loader
     *
     * @return $this
     */
    public function addLoader(LoaderInterface $loader)
    {
        $this->loaders[$loader->getExtension()] = $loader;

        return $this;
    }

    /**
     * @return TemplateCollection
     */
    public function loadTemplates()
    {
        $templates = new TemplateCollection();

        foreach ($this->loaders as $extension => $loader) {
            $files = $this->locator->locate("*.$extension", 'mediatemplates', false);

            foreach ($files as $file) {
                $templates->add($loader->load($file));
            }
        }

        return $templates;
    }
}
