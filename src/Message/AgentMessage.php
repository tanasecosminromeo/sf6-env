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
        return sprintf('AgentMessage { content: %s, type: %d }', $this->content, $this->getTypeName());
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): int
    {
        return $this->type;
    }

    static function getTypeNames(): array
    {
        return [
            self::TO_LLM => 'TO_LLM',
            self::TO_GOOGLE => 'TO_GOOGLE',
            self::TO_STORAGE => 'TO_STORAGE',
        ];
    }

    public function getTypeName(): string
    {
        return self::getTypeNames()[$this->type] ?? 'unknown';
    }
}
