<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Contract;

use Token27\NexusAI\Agents\Core\AgentContext;

/**
 * Defines the prompt format and response parsing for a planning strategy.
 *
 * Each strategy (ReAct, ChainOfThought, PlanAndExecute) provides its own
 * system prompt template, formats the input for the LLM, and parses the
 * structured response into steps.
 *
 * @see \Token27\NexusAI\Agents\Enum\PlanningStrategy
 * @see \Token27\NexusAI\Agents\Contract\PlannerInterface
 */
interface PlanningStrategyInterface
{
    /**
     * Returns the system prompt that instructs the LLM how to think.
     *
     * @return string The system prompt template.
     */
    public function getSystemPrompt(): string;

    /**
     * Formats the user input and context into the LLM prompt.
     *
     * @param string $input The user's input text.
     * @param AgentContext $context The current agent context.
     * @return string The formatted prompt for the LLM.
     */
    public function formatInput(string $input, AgentContext $context): string;

    /**
     * Parses the LLM response into structured data.
     *
     * Each strategy has its own output format (e.g., ReAct's
     * Thought/Action/Observation blocks). This method extracts
     * the relevant information from the raw LLM text.
     *
     * @param string $response The raw LLM response text.
     * @return array<string, mixed> Parsed response data.
     */
    public function parseResponse(string $response): array;
}
