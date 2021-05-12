<?php
namespace App\Tests\api;

class RequestObject
{
    private $context;
    private $data;
    private $iri;
    
    public function __construct(array $context, array $data)
    {
        $this->context = $context;
        $this->data    = $data;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getIri(): string
    {
        return $this->iri;
    }

    public function getName(): string
    {
        return $this->data['name'];
    }
    /**
     * @param array $context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param string $iri
     */
    public function setIri(string $iri): void
    {
        $this->iri = $iri;
    }
}

