<?php

namespace Droid\Transform;

use Exception;
use InvalidArgumentException;

class Transformer
{
    protected $dataStreamTransformer;
    protected $fileTransformer;
    protected $inventoryTransformer;
    protected $substitutionTransformer;

    public function __construct(
        DataStreamTransformer $dataStreamTransformer,
        FileTransformer $fileTransformer,
        InventoryTransformer $inventoryTransformer,
        SubstitutionTransformer $substitutionTransformer
    ) {
        $this->dataStreamTransformer = $dataStreamTransformer;
        $this->fileTransformer = $fileTransformer;
        $this->inventoryTransformer = $inventoryTransformer;
        $this->substitutionTransformer = $substitutionTransformer;
    }

    public function transformDataStream($data)
    {
        $result = null;

        try {
            $result = $this->dataStreamTransformer->transform($data);
        } catch (InvalidArgumentException $e) {
            throw new TransformerException(
                'The supplied data could not be transformed into a data stream.',
                null,
                $e
            );
        } catch (Exception $e) {
            throw new TransformerException(
                'The failure to transform the supplied data is unexpected.',
                null,
                $e
            );
        }

        return $result;
    }

    public function transformFile($filename)
    {
        $result = null;

        try {
            $result = $this->fileTransformer->transform($filename);
        } catch (InvalidArgumentException $e) {
            throw new TransformerException(
                'The supplied data could not be transformed into file content.',
                null,
                $e
            );
        } catch (Exception $e) {
            throw new TransformerException(
                'The failure to transform the supplied data is unexpected.',
                null,
                $e
            );
        }

        return $result;
    }

    public function transformInventory($expression)
    {
        $result = null;

        try {
            $result = $this->inventoryTransformer->transform($expression);
        } catch (TransformerException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            throw new TransformerException(
                'The supplied data could not be transformed into a property from the Inventory.',
                null,
                $e
            );
        } catch (Exception $e) {
            throw new TransformerException(
                'The failure to transform the supplied data is unexpected.',
                null,
                $e
            );
        }

        return $result;
    }

    public function transformVariable($name, $values)
    {
        $result = null;

        try {
            $result = $this->substitutionTransformer->transform($name, $values);
        } catch (TransformerException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            throw new TransformerException(
                'The supplied name could not substituted with variable data.',
                null,
                $e
            );
        } catch (Exception $e) {
            throw new TransformerException(
                'The failure to transform the supplied data is unexpected.',
                null,
                $e
            );
        }

        return $result;
    }

    /**
     * Add templates to the substitution transformer.
     *
     * Each entry in the supplied array of templates should be the string
     * template content, keyed by the name used to look up the template.
     *
     * @param array $templates
     */
    public function addTemplates($templates)
    {
        $this->substitutionTransformer->addTemplates($templates);
    }
}
