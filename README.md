# AbstractEngine - Object-Oriented Simulation Engine

## Problem Statement

Traditional game engines often tightly couple game logic with engine code, making it difficult to reuse components across different projects. This creates maintenance challenges and limits extensibility when building multiple simulations or games.

AbstractEngine addresses this by providing a **completely abstract, zero-dependency simulation engine** built from scratch in Java. The engine demonstrates professional software architecture with zero hardcoded game logic, enabling developers to build any type of simulation by simply extending base classes.

## Features

### Scene Management System
- **Abstract Scene Lifecycle**: Start, stop, update, and render methods for complete scene control
- **Seamless Transitions**: Switch between Menu, Main, and End scenes without state loss
- **State Pattern Implementation**: Clean separation of different simulation states
- **Extensible Design**: Add new scenes by extending the Scene base class

### Entity Component System
- **Abstract Entity Base**: Flexible entity architecture supporting any game object type
- **Component-Based Design**: Add functionality through composition, not inheritance
- **Lifecycle Management**: Automatic creation, activation, update, render, and disposal
- **Multiple Entity Types**: Player, NPC, TextureObject with shared base functionality

### Collision Detection System
- **Fully Abstract**: Zero hardcoded entity types - works with any simulation
- **Layer-Based Detection**: Efficient AABB (Axis-Aligned Bounding Box) collision
- **Event-Driven Callbacks**: Register custom collision handlers for any entity pair
- **Generic Wall Handling**: Obstacle collision works for any entity type
- **Reusable Across Projects**: No simulation-specific code in collision system

### Input Management
- **Device-Independent Actions**: Abstract InputAction enum for rebindable controls
- **Multiple Input Devices**: Keyboard and Mouse with unified interface
- **InputProcessor Interface**: Easy to add new input devices (gamepad, touch, etc.)
- **Flexible Key Mapping**: Rebind controls without changing game logic

### Audio Output System
- **Abstract Audio Interface**: SoundOut interface defines audio contract
- **Speaker Implementation**: Complete audio playback with volume control
- **Sound Resource Management**: Load, play, pause, stop, and loop sounds
- **Integrated System**: Managed through InputOutputManager for clean architecture

### Movement System
- **Velocity-Based Physics**: Smooth, frame-rate independent movement
- **IMovable Interface**: Define which entities can move
- **Input-Driven Control**: Connect input actions to entity movement
- **Delta Time Integration**: Consistent movement regardless of frame rate

### Time Management System
- **Delta Time Calculation**: Frame-rate independent updates
- **Time Scale Support**: Slow-motion and fast-forward effects
- **Pause/Resume**: Complete simulation pause functionality
- **Total Time Tracking**: Track elapsed simulation time

## Key Java Implementations

### 1. Abstract Scene System with Lifecycle Management
**File:** `engine/scene/Scene.java`

```java
public abstract class Scene {
    protected String name;
    protected boolean isActive;
    
    public Scene(String name) {
        this.name = name;
        this.isActive = false;
    }
    
    // Abstract lifecycle methods - must be implemented by subclasses
    public abstract void start();
    public abstract void stop();
    public abstract void update(float deltaTime);
    public abstract void render();
    
    // Concrete methods for state management
    public void activate() { this.isActive = true; start(); }
    public void deactivate() { this.isActive = false; stop(); }
    public boolean isActive() { return isActive; }
    public String getName() { return name; }
}
```

**Demonstrates:** Abstract class design, template method pattern, lifecycle management, and encapsulation of scene state.

### 2. Generic Collision Detection with Event Callbacks
**File:** `engine/collision/DetectCollision.java`

```java
public class DetectCollision {
    public static boolean checkAABB(Entity a, Entity b) {
        float ax1 = a.getX();
        float ay1 = a.getY();
        float ax2 = ax1 + a.getWidth();
        float ay2 = ay1 + a.getHeight();
        
        float bx1 = b.getX();
        float by1 = b.getY();
        float bx2 = bx1 + b.getWidth();
        float by2 = by1 + b.getHeight();
        
        return ax1 < bx2 && ax2 > bx1 && ay1 < by2 && ay2 > by1;
    }
    
    public static void detectCollisions(List<Entity> entities, 
                                       CollisionManager manager) {
        for (int i = 0; i < entities.size(); i++) {
            for (int j = i + 1; j < entities.size(); j++) {
                Entity a = entities.get(i);
                Entity b = entities.get(j);
                
                if (checkAABB(a, b)) {
                    manager.handleCollision(a, b);
                }
            }
        }
    }
}
```

**Demonstrates:** AABB collision algorithm, nested loop optimization, geometric calculations, and separation of detection from response.

### 3. Observer Pattern for Collision Events
**File:** `engine/collision/CollisionManager.java`

```java
public class CollisionManager {
    private Map<String, ICollisionListener> handlers;
    
    public CollisionManager() {
        this.handlers = new HashMap<>();
    }
    
    public void registerHandler(String layerA, String layerB, 
                               ICollisionListener listener) {
        String key = createKey(layerA, layerB);
        handlers.put(key, listener);
    }
    
    public void handleCollision(Entity a, Entity b) {
        String key = createKey(a.getLayer(), b.getLayer());
        ICollisionListener listener = handlers.get(key);
        
        if (listener != null) {
            listener.onCollision(a, b);
        }
    }
    
    private String createKey(String layerA, String layerB) {
        return layerA.compareTo(layerB) < 0 
            ? layerA + ":" + layerB 
            : layerB + ":" + layerA;
    }
}
```

**Demonstrates:** Observer pattern, HashMap for O(1) lookups, interface-based callbacks, and symmetric key generation for bidirectional collision handling.

### 4. Delta Time Management for Frame-Rate Independence
**File:** `engine/managers/TimeManager.java`

```java
public class TimeManager {
    private long lastFrameTime;
    private float deltaTime;
    private float timeScale;
    private boolean isPaused;
    private float totalTime;
    
    public TimeManager() {
        this.lastFrameTime = System.nanoTime();
        this.deltaTime = 0.0f;
        this.timeScale = 1.0f;
        this.isPaused = false;
        this.totalTime = 0.0f;
    }
    
    public void update() {
        long currentTime = System.nanoTime();
        long elapsed = currentTime - lastFrameTime;
        lastFrameTime = currentTime;
        
        deltaTime = elapsed / 1_000_000_000.0f;
        
        if (!isPaused) {
            deltaTime *= timeScale;
            totalTime += deltaTime;
        } else {
            deltaTime = 0.0f;
        }
    }
    
    public float getDeltaTime() { return deltaTime; }
    public void setTimeScale(float scale) { this.timeScale = scale; }
    public void pause() { this.isPaused = true; }
    public void resume() { this.isPaused = false; }
    public float getTotalTime() { return totalTime; }
}
```

**Demonstrates:** Time calculation using System.nanoTime(), frame-rate independence, time scaling for game effects, and pause functionality.

### 5. Composition-Based Architecture with GameMaster
**File:** `engine/core/GameMaster.java`

```java
public class GameMaster {
    private SceneManager sceneManager;
    private EntityManager entityManager;
    private CollisionManager collisionManager;
    private MovementManager movementManager;
    private InputOutputManager inputOutputManager;
    private TimeManager timeManager;
    private boolean running;
    
    public GameMaster() {
        this.sceneManager = new SceneManager();
        this.entityManager = new EntityManager();
        this.collisionManager = new CollisionManager();
        this.movementManager = new MovementManager();
        this.inputOutputManager = new InputOutputManager();
        this.timeManager = new TimeManager();
        this.running = false;
    }
    
    public void start() {
        running = true;
        gameLoop();
    }
    
    private void gameLoop() {
        while (running) {
            timeManager.update();
            float dt = timeManager.getDeltaTime();
            
            inputOutputManager.processInput();
            sceneManager.update(dt);
            entityManager.update(dt);
            movementManager.update(dt);
            collisionManager.detectAndHandle(entityManager.getActiveEntities());
            sceneManager.render();
            
            sceneManager.handleTransitions();
        }
    }
    
    // Getters for all managers
    public SceneManager getSceneManager() { return sceneManager; }
    public EntityManager getEntityManager() { return entityManager; }
    // ... other getters
}
```

**Demonstrates:** Composition over inheritance, manager pattern, game loop architecture, and centralized system orchestration.

### 6. Interface-Based Input System
**File:** `engine/input/InputProcessor.java`, `engine/input/Keyboard.java`

```java
// Interface defining input contract
public interface InputProcessor {
    void processInput();
    boolean isActionPressed(InputAction action);
    void bindAction(InputAction action, int keyCode);
}

// Concrete implementation for keyboard
public class Keyboard implements InputProcessor {
    private Map<InputAction, Integer> keyBindings;
    private Set<Integer> pressedKeys;
    
    public Keyboard() {
        this.keyBindings = new HashMap<>();
        this.pressedKeys = new HashSet<>();
        setupDefaultBindings();
    }
    
    private void setupDefaultBindings() {
        keyBindings.put(InputAction.MOVE_UP, KeyEvent.VK_W);
        keyBindings.put(InputAction.MOVE_DOWN, KeyEvent.VK_S);
        keyBindings.put(InputAction.MOVE_LEFT, KeyEvent.VK_A);
        keyBindings.put(InputAction.MOVE_RIGHT, KeyEvent.VK_D);
    }
    
    @Override
    public boolean isActionPressed(InputAction action) {
        Integer keyCode = keyBindings.get(action);
        return keyCode != null && pressedKeys.contains(keyCode);
    }
    
    @Override
    public void bindAction(InputAction action, int keyCode) {
        keyBindings.put(action, keyCode);
    }
}
```

**Demonstrates:** Interface-based design, polymorphism, HashMap for key bindings, and device-independent input abstraction.

### 7. Vector Mathematics for 2D Physics
**File:** `engine/utils/Vector2.java`

```java
public class Vector2 {
    public float x;
    public float y;
    
    public Vector2(float x, float y) {
        this.x = x;
        this.y = y;
    }
    
    public Vector2 add(Vector2 other) {
        return new Vector2(this.x + other.x, this.y + other.y);
    }
    
    public Vector2 multiply(float scalar) {
        return new Vector2(this.x * scalar, this.y * scalar);
    }
    
    public float magnitude() {
        return (float) Math.sqrt(x * x + y * y);
    }
    
    public Vector2 normalize() {
        float mag = magnitude();
        if (mag == 0) return new Vector2(0, 0);
        return new Vector2(x / mag, y / mag);
    }
    
    public static float distance(Vector2 a, Vector2 b) {
        float dx = b.x - a.x;
        float dy = b.y - a.y;
        return (float) Math.sqrt(dx * dx + dy * dy);
    }
}
```

**Demonstrates:** Immutable design pattern, mathematical operations, Pythagorean theorem, vector normalization, and static utility methods.

## Technical Architecture

### Engine Core
- **GameMaster**: Central orchestrator managing all subsystems
- **Manager Pattern**: Specialized managers for each system (Scene, Entity, Collision, etc.)
- **Composition**: Systems composed together rather than inherited
- **Loose Coupling**: Managers communicate through abstract interfaces

### Design Principles
- **Zero Hardcoded Logic**: Engine contains no simulation-specific code
- **Complete Abstraction**: All game logic in simulation layer, not engine
- **Interface-Based**: Contracts defined through interfaces and abstract classes
- **Extensible**: Add new functionality by extending, not modifying

### OOP Principles Demonstrated
- **Abstraction**: Scene and Entity abstract classes, InputProcessor interface
- **Inheritance**: MenuScene/MainScene/EndScene extend Scene
- **Polymorphism**: Process entities through Entity base reference
- **Encapsulation**: Private fields with public getters/setters
- **Composition**: GameMaster composes all managers

## How to Run

### Compile the project
```bash
cd src
javac -d ../bin engine/**/*.java simulation/*.java
```

### Run the demo simulation
```bash
cd ../bin
java simulation.PlayableGame
```

### Or use IDE
Right-click on `PlayableGame.java` and select "Run as Java Application"

## How to Play

1. **Launch the game** using one of the methods above
2. **Menu Screen**: Press ENTER to start the simulation
3. **Game Controls**:
   - **W** - Move up
   - **A** - Move left
   - **S** - Move down
   - **D** - Move right
   - **SPACE** - Pause/Resume
4. **Objective**: Navigate the player, avoid obstacles, interact with NPCs
5. **End Screen**: View your results and press ENTER to restart

## Project Structure

```
AbstractEngine/
├── src/
│   ├── engine/                      # Core engine (100% abstract)
│   │   ├── core/
│   │   │   └── GameMaster.java      # Central system orchestrator
│   │   ├── managers/
│   │   │   ├── SceneManager.java    # Scene lifecycle & transitions
│   │   │   ├── EntityManager.java   # Entity lifecycle management
│   │   │   ├── CollisionManager.java # Collision detection & response
│   │   │   ├── MovementManager.java  # Movement system
│   │   │   ├── InputOutputManager.java # I/O handling
│   │   │   └── TimeManager.java     # Delta time & pause
│   │   ├── scene/
│   │   │   ├── Scene.java           # Abstract scene base
│   │   │   ├── MenuScene.java       # Menu scene implementation
│   │   │   ├── MainScene.java       # Main game scene
│   │   │   └── EndScene.java        # End screen
│   │   ├── entities/
│   │   │   ├── Entity.java          # Abstract entity base
│   │   │   ├── EntityComponent.java # Component base class
│   │   │   ├── IMovable.java        # Movable interface
│   │   │   ├── Renderable.java      # Renderable interface
│   │   │   ├── Player.java          # Player entity
│   │   │   ├── NPC.java             # NPC entity
│   │   │   └── TextureObject.java   # Visual object
│   │   ├── collision/
│   │   │   ├── ICollisionListener.java # Collision callback
│   │   │   ├── DetectCollision.java    # AABB detection
│   │   │   └── HandleCollision.java    # Response handling
│   │   ├── input/
│   │   │   ├── InputAction.java     # Action enumeration
│   │   │   ├── InputProcessor.java  # Input interface
│   │   │   ├── Keyboard.java        # Keyboard handler
│   │   │   └── Mouse.java           # Mouse handler
│   │   ├── output/
│   │   │   ├── OutputProcessor.java # Abstract output
│   │   │   ├── SoundOut.java        # Audio interface
│   │   │   ├── Sound.java           # Sound resource
│   │   │   └── Speaker.java         # Audio device
│   │   └── utils/
│   │       └── Vector2.java         # 2D vector math
│   └── simulation/                  # Simulation implementations
│       ├── EngineSimulation.java    # Console demo
│       └── PlayableGame.java        # Full playable demo
├── bin/                             # Compiled classes
├── build.sh                         # Linux/Mac build script
├── build.bat                        # Windows build script
└── README.md
```

## Design Patterns Used

1. **Manager Pattern**: Centralized system management (SceneManager, EntityManager, etc.)
2. **State Pattern**: Scene system for different simulation states
3. **Observer Pattern**: Collision event listeners with callbacks
4. **Strategy Pattern**: Pluggable collision detection and handling
5. **Template Method**: Abstract Scene/Entity with concrete implementations
6. **Component Pattern**: Entity component system for flexible behavior
7. **Facade Pattern**: GameMaster provides simplified interface to complex subsystems

## Extensibility Examples

### Adding a New Scene
```java
public class CustomScene extends Scene {
    public CustomScene() {
        super("Custom");
    }
    
    @Override
    public void start() {
        // Initialize scene
    }
    
    @Override
    public void stop() {
        // Cleanup scene
    }
    
    @Override
    public void update(float dt) {
        // Update logic
    }
    
    @Override
    public void render() {
        // Render scene
    }
}
```

### Adding a New Entity Type
```java
public class Enemy extends Entity implements IMovable {
    private Vector2 velocity;
    
    public Enemy(float x, float y) {
        super(x, y, 32, 32, "enemy");
        this.velocity = new Vector2(0, 0);
    }
    
    @Override
    public void update(float deltaTime) {
        // AI logic
        x += velocity.x * deltaTime;
        y += velocity.y * deltaTime;
    }
    
    @Override
    public void render() {
        System.out.println("Enemy at: " + x + ", " + y);
    }
    
    @Override
    public Vector2 getVelocity() { return velocity; }
    
    @Override
    public void setVelocity(Vector2 v) { this.velocity = v; }
}
```

### Registering Custom Collision Handler
```java
collisionManager.registerHandler("player", "enemy", 
    new ICollisionListener() {
        @Override
        public void onCollision(Entity a, Entity b) {
            System.out.println("Player hit enemy!");
            // Damage player, destroy enemy, etc.
        }
    }
);
```

### Using the Audio System
```java
Speaker speaker = gameMaster.getInputOutputManager().getSpeaker();

// Load and play sounds
speaker.loadSound("jump", "sounds/jump.wav");
speaker.play("jump");

// Background music with looping
speaker.loadSound("bgm", "sounds/music.mp3");
speaker.setLooping("bgm", true);
speaker.play("bgm", 0.5f); // 50% volume

// Control playback
speaker.pause("bgm");
speaker.resume("bgm");
speaker.setVolume(0.8f);
```

## System Requirements

- **Java Development Kit (JDK)**: Version 8 or higher
- **Dependencies**: None - 100% pure Java implementation
- **Operating System**: Cross-platform (Windows, macOS, Linux)

## Educational Value

This project demonstrates fundamental Java and OOP concepts:

### Core Java Concepts
- **Classes & Objects**: Entity, Scene, Manager classes
- **Inheritance**: Scene/Entity hierarchies
- **Interfaces**: InputProcessor, IMovable, ICollisionListener
- **Abstract Classes**: Scene, Entity, OutputProcessor
- **Polymorphism**: Base class references for flexible code
- **Encapsulation**: Private fields with controlled access
- **Composition**: GameMaster composes managers

### Advanced Concepts
- **Design Patterns**: Manager, Observer, State, Strategy, Template Method
- **Data Structures**: ArrayList, HashMap, HashSet for efficient storage
- **Algorithms**: AABB collision detection, delta time calculation
- **Event Systems**: Callback-based collision handling
- **Game Loop**: Fixed timestep simulation loop
- **Time Management**: Frame-rate independent updates

### Software Engineering
- **Separation of Concerns**: Engine vs. simulation layers
- **Loose Coupling**: Interface-based communication
- **High Cohesion**: Single responsibility per class
- **Extensibility**: Open for extension, closed for modification
- **Reusability**: Zero hardcoded logic enables reuse

## Demo Simulations

### EngineSimulation
Console-based demonstration showing:
- Entity creation and management
- Collision detection
- Input handling
- Scene transitions

### PlayableGame
Complete interactive simulation featuring:
- **Menu System**: Navigate between scenes
- **Player Control**: WASD movement with collision
- **NPC Behaviors**: Patrol and wander AI patterns
- **Collision System**: Player-enemy and wall collisions
- **Pause Functionality**: Spacebar to pause/resume
- **End Screen**: Score tracking and results
- **Visual Rendering**: Java Swing-based graphics

## Key Technical Achievements

✅ **True Abstraction**: Engine core contains zero simulation-specific logic  
✅ **Zero Dependencies**: No external libraries required  
✅ **Complete Reusability**: Use for any 2D simulation project  
✅ **Professional Architecture**: Industry-standard design patterns  
✅ **Frame-Rate Independence**: Delta time for consistent behavior  
✅ **Event-Driven**: Callback-based collision and input systems  
✅ **Extensible Design**: Add features without modifying engine  

## Future Enhancements

- Graphics rendering (texture loading, sprite batching)
- Animation system with sprite sheets
- Particle effects system
- Advanced physics (gravity, friction, forces)
- Spatial partitioning (quadtree) for collision optimization
- Save/load system for game state
- Networking for multiplayer
- Scripting support (Lua/JavaScript integration)
- Level editor and serialization

---

**This project demonstrates professional game engine architecture and OOP best practices. The engine is completely abstract and reusable across different simulation projects without modification.**
