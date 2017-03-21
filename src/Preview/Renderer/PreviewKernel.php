<?php

namespace ConnectHolland\Sulu\SME\Preview\Renderer;

use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewKernel as BasePreviewKernel;

class PreviewKernel extends BasePreviewKernel
{
    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $reflectionClass = new \ReflectionClass(\WebsiteKernel::class);
            $this->rootDir = realpath(dirname($reflectionClass->getFileName()).'/../../../../app');
        }

        return $this->rootDir;
    }
}
