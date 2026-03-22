package engine.core;

import engine.managers.*;
import engine.scene.*;

/**
 * GameMaster - Main engine controller
 * Coordinates all managers and handles the game loop
 */
public class GameMaster {
    // Managers
    private SceneManager sceneManager;
    private EntityManager entityManager;
    private CollisionManager collisionManager;
    private MovementManager movementManager;
    private InputOutputManager inputOutputManager;
    private TimeManager timeManager;
    
    // Engine state
    private boolean running;
    private boolean paused;
    private boolean initialized;
    
    // Timing
    private long lastFrameTime;
    private float targetFPS;
    private float frameTime; // Calculated frame time based on target FPS
    
    public GameMaster() {
        this.running = false;
        this.paused = false;
        this.initialized = false;
        this.targetFPS = 60.0f;
        this.frameTime = 1.0f / targetFPS;
    }
    
    /**
     * Initialize the engine and all managers
     */
    public void initialize() {
        System.out.println("GameMaster: Initializing engine...");
        
        // Create managers
        timeManager = new TimeManager();
        inputOutputManager = new InputOutputManager();
        entityManager = new EntityManager();
        collisionManager = new CollisionManager(entityManager);
        movementManager = new MovementManager(inputOutputManager);
        sceneManager = new SceneManager();
        
        // Start TimeManager
        timeManager.start();
        
        // Register scenes
        registerScenes();
        
        initialized = true;
        System.out.println("GameMaster: Engine initialized successfully");
    }
    
    /**
     * Register all scenes with the scene manager
     */
    private void registerScenes() {
        MenuScene menuScene = new MenuScene(inputOutputManager);
        MainScene mainScene = new MainScene(entityManager, movementManager, 
                                           collisionManager, inputOutputManager);
        EndScene endScene = new EndScene(inputOutputManager);
        
        sceneManager.addScene("menu", menuScene);
        sceneManager.addScene("main", mainScene);
        sceneManager.addScene("end", endScene);
    }
    
    /**
     * Start the engine
     */
    public void start() {
        if (!initialized) {
            System.err.println("GameMaster: Cannot start - engine not initialized!");
            return;
        }
        
        System.out.println("GameMaster: Starting engine...");
        running = true;
        paused = false;
        lastFrameTime = System.nanoTime();
        
        // Ensure TimeManager is started
        if (timeManager != null) {
            timeManager.start();
        }
        
        // Start with menu scene
        sceneManager.startScene("menu");
        
        System.out.println("GameMaster: Engine started");
    }
    
    /**
     * Main game loop tick
     * Call this repeatedly to run the engine
     */
    public void tick(float deltaTime) {
        if (!running || paused) return;
        
        // Update input
        inputOutputManager.pollInput();
        
        // Process output (audio, etc.)
        inputOutputManager.processOutput();
        
        // Update current scene
        sceneManager.update(deltaTime);
        
        // Handle scene transitions
        handleSceneTransitions();
    }
    
    /**
     * Update with automatic delta time calculation
     */
    public void update() {
        if (!running) return;
        
        long currentTime = System.nanoTime();
        float deltaTime = (currentTime - lastFrameTime) / 1_000_000_000.0f;
        lastFrameTime = currentTime;
        
        timeManager.update(deltaTime);
        float dt = timeManager.getDeltaTime();
        tick(dt);
        render(null);
    }
    
    /**
     * Render the current scene
     */
    public void render(Object batch) {
        if (!running) return;
        
        sceneManager.render(batch);
    }
    
    /**
     * Render without batch parameter (convenience method)
     */
    public void render() {
        render(null);
    }
    
    /**
     * Handle scene transitions based on scene state
     */
    private void handleSceneTransitions() {
        Scene currentScene = sceneManager.getActiveScene();
        if (currentScene == null) return;
        
        // Check menu scene for transition
        if (currentScene instanceof MenuScene) {
            MenuScene menu = (MenuScene) currentScene;
            if (menu.isTransitionRequested()) {
                menu.resetTransition();
                sceneManager.startScene("main");
            }
        }
        
        // Check main scene for game over
        else if (currentScene instanceof MainScene) {
            MainScene main = (MainScene) currentScene;
            if (main.isGameOver()) {
                EndScene endScene = (EndScene) sceneManager.getScene("end");
                if (endScene != null) {
                    endScene.setResults(0, main.getGameTime()); // Score=0 for now
                    sceneManager.startScene("end");
                }
            }
        }
        
        // Check end scene for restart/exit
        else if (currentScene instanceof EndScene) {
            EndScene end = (EndScene) currentScene;
            if (end.isRestartRequested()) {
                end.resetRequests();
                // Clear previous game state
                entityManager.clear();
                sceneManager.startScene("main");
            } else if (end.isExitRequested()) {
                end.resetRequests();
                sceneManager.startScene("menu");
            }
        }
    }
    
    /**
     * Pause the engine
     */
    public void pause() {
        paused = true;
        if (timeManager != null) {
            timeManager.pause();
        }
        System.out.println("GameMaster: Engine paused");
    }
    
    /**
     * Resume the engine
     */
    public void resume() {
        paused = false;
        lastFrameTime = System.nanoTime();
        if (timeManager != null) {
            timeManager.resume();
        }
        System.out.println("GameMaster: Engine resumed");
    }
    
    /**
     * Stop the engine
     */
    public void end() {
        System.out.println("GameMaster: Stopping engine...");
        running = false;
        
        // Stop current scene
        Scene currentScene = sceneManager.getActiveScene();
        if (currentScene != null) {
            currentScene.stop();
        }
        
        System.out.println("GameMaster: Engine stopped");
    }
    
    /**
     * Dispose of all resources
     */
    public void dispose() {
        System.out.println("GameMaster: Disposing resources...");
        
        if (running) {
            end();
        }
        
        if (timeManager != null) {
            timeManager.dispose();
        }
        if (entityManager != null) {
        entityManager.dispose();
        }
        initialized = false;
        
        System.out.println("GameMaster: Resources disposed");
    }
    
    // Getters
    public boolean isRunning() {
        return running;
    }
    
    public boolean isPaused() {
        return paused;
    }
    
    public boolean isInitialized() {
        return initialized;
    }
    
    public SceneManager getSceneManager() {
        return sceneManager;
    }
    
    public EntityManager getEntityManager() {
        return entityManager;
    }
    
    public CollisionManager getCollisionManager() {
        return collisionManager;
    }
    
    public MovementManager getMovementManager() {
        return movementManager;
    }
    
    public InputOutputManager getInputOutputManager() {
        return inputOutputManager;
    }
    
    public float getTargetFPS() {
        return targetFPS;
    }
    
    public void setTargetFPS(float fps) {
        this.targetFPS = fps;
        this.frameTime = 1.0f / fps;
    }
    
    /**
     * Get the calculated frame time based on target FPS
     */
    public float getFrameTime() {
        return frameTime;
    }
    public TimeManager getTimeManager() {
        return timeManager;
	}
}
