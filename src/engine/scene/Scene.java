package engine.scene;

import engine.entities.Entity;
import java.util.ArrayList;
import java.util.List;

/**
 * Abstract base class for all scenes in the engine
 * Defines the scene lifecycle and required methods
 */
public abstract class Scene {
    protected String sceneName;
    protected List<Entity> entities;
    protected boolean isRunning;
    
    public Scene(String sceneName) {
        this.sceneName = sceneName;
        this.entities = new ArrayList<>();
        this.isRunning = false;
    }
    
    /**
     * Get the scene name
     */
    public String getSceneName() {
        return sceneName;
    }
    
    /**
     * Check if scene is currently running
     */
    public boolean isRunning() {
        return isRunning;
    }
    
    /**
     * Start the scene
     * Called when scene becomes active
     */
    public abstract void start();
    
    /**
     * Stop the scene
     * Called when scene is deactivated
     */
    public abstract void stop();
    
    /**
     * Render the scene
     * Called every frame to draw the scene
     */
    public abstract void render();
    
    /**
     * Update the scene
     * Called every frame to update scene logic
     * @param dt Delta time since last update
     */
    public abstract void update(float dt);
    
    /**
     * Load scene assets
     * Called during scene initialization
     */
    protected abstract void loadAssets();
    
    /**
     * Handle input for this scene
     * Called before update
     */
    protected abstract void handleInput();
    
    /**
     * Draw scene-specific content
     * Called during render
     */
    protected abstract void draw();
    
    /**
     * Load the scene
     */
    public void load() {
        loadAssets();
    }
    
    /**
     * Unload the scene
     */
    public void unload() {
        entities.clear();
    }
    
    /**
     * Draw word (legacy method name)
     */
    public void drawWord() {
        draw();
    }
    
    /**
     * Draw results
     */
    public void drawResults() {
        // Override in subclasses that need to draw results
    }
    
    @Override
    public String toString() {
        return String.format("Scene[name=%s, running=%b]", sceneName, isRunning);
    }
}
