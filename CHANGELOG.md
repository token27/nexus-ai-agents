# Changelog

All notable changes to `nexus-ai-agents` will be documented in this file.

## [1.0.0] — Initial Release

### Added

- **Agent Core**: `AgentInterface`, `AbstractAgent`, `AgentConfig`, `AgentContext`, `AgentResult`
- **Planning Strategies**: ReAct, Chain-of-Thought, Plan-and-Execute with `PlannerFactory`
- **Memory System**: Working, Episodic, Semantic memory with `MemoryManager` and `MemoryTrimmer`
- **Tool Integration**: `ToolPolicy`, `ToolApprovalMiddleware`, `HumanInTheLoop`
- **Multi-Agent**: `MessageBus`, `Handoff`, `AgentTeam`, `OrchestratorAgent`
- **Observability**: 6 event types (`AgentStarted`, `AgentStep`, `AgentReflection`, `AgentToolCall`, `AgentCompleted`, `AgentFailed`)
- **Middleware Pipeline**: `MemoryInjectionMiddleware`, `BudgetGuardMiddleware`, `PlanningMiddleware`
- **Zero Framework Dependencies**: Only PSR interfaces + `nexus-ai` + `nexus-ai-workflows`
- **PHP 8.2+**: Readonly classes, enums, named parameters
