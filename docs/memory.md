# Memory

Three memory types for agent persistence across interactions.

## WorkingMemory

Short-term context for the current task:

```php
use Token27\NexusAI\Agents\Memory\WorkingMemory;

$memory = new WorkingMemory();
$memory->set('current_topic', 'PHP');
$memory->get('current_topic');            // 'PHP'
$memory->has('current_topic');            // true
$memory->clear();
```

## EpisodicMemory

Stores past interactions and outcomes:

```php
use Token27\NexusAI\Agents\Memory\EpisodicMemory;

$memory = new EpisodicMemory();
$memory->record('user asked about PHP');
$memory->record('provided SOLID principles explanation');

$episodes = $memory->recall(limit: 5);    // Last 5 entries
```

## SemanticMemory

Long-term knowledge base with key-value storage:

```php
use Token27\NexusAI\Agents\Memory\SemanticMemory;

$memory = new SemanticMemory();
$memory->store('php_version', 'PHP 8.4 released in November 2024');
$memory->retrieve('php_version');
```

## MemoryManager

Coordinates all three memory types:

```php
use Token27\NexusAI\Agents\Memory\MemoryManager;

$manager = new MemoryManager(
    working: new WorkingMemory(),
    episodic: new EpisodicMemory(),
    semantic: new SemanticMemory(),
);

$manager->getWorking()->set('key', 'value');
$manager->getEpisodic()->record('event');
$manager->getSemantic()->store('fact_key', 'fact_value');
```

## InMemoryStore

Backend for memory persistence:

```php
use Token27\NexusAI\Agents\Memory\InMemoryStore;

$store = new InMemoryStore();
// Used internally by memory components
```

## MemoryTrimmer

Prevents memory from growing unbounded:

```php
use Token27\NexusAI\Agents\Memory\MemoryTrimmer;

$trimmer = new MemoryTrimmer(maxEntries: 100);
$trimmer->trim($episodicMemory);
```

---

> **Next:** [Middleware →](middleware.md)
