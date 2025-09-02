<?php

namespace App\Message;

class AgentMessage
{
    private string $content;

    private int $type;

    const TO_LLM = 0;
    const TO_GOOGLE = 1;
    const TO_STORAGE = 2;

    public function __construct(string $content, int $type = self::TO_LLM)
    {
        $this->content = $content;
        $this->type = $type;
    }

    public function __toString()
    {
        return sprintf('AgentMessage { content: %s, type: %d }', $this->content, $this->type);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
