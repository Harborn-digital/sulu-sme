<?php

namespace ConnectHolland\Sulu\SME\Preview\Renderer;

use Sulu\Bundle\PreviewBundle\Preview\Renderer\WebsiteKernelFactory as BaseWebsiteKernelFactory;

class WebsiteKernelFactory extends BaseWebsiteKernelFactory
{
    /**
     * {@inheritdoc}
     */
    public function create($environment)
    {
        $kernel = new PreviewKernel($environment, $environment === 'dev');
        $kernel->boot();

        return $kernel;
    }
}
