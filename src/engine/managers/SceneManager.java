package engine.managers;

import engine.scene.Scene;
import java.util.ArrayList;
import java.util.List;

/**
 * Manages scenes and scene transitions
 * Handles scene lifecycle and switching between scenes
 */
public class SceneManager {
    private List<Scene> scenes;
    private Scene currentScene;
    
    public SceneManager() {
        this.scenes = new ArrayList<>();
        this.currentScene = null;
    }
    
    /**
     * Start the scene manager
     */
    public void start() {
        if (currentScene != null) {
            currentScene.start();
        }
    }
    
    /**
     * Load a scene by name
     */
    public void loadScene(String name) {
        Scene scene = getScene(name);
        if (scene != null) {
            if (currentScene != null) {
                unloadScene();
            }
            currentScene = scene;
            currentScene.start();
        }
    }
    
    /**
     * Unload a scene by name
     */
    public void unloadScene(String name) {
        Scene scene = getScene(name);
        if (scene != null) {
            if (scene == currentScene) {
                currentScene = null;
            }
            scene.stop();
        }
    }
    
    /**
     * Unload current scene (convenience method)
     */
    public void unloadScene() {
        if (currentScene != null) {
            currentScene.stop();
            currentScene = null;
        }
    }
    
    /**
     * Register a scene with the manager
     */
    public void addScene(String name, Scene scene) {
        if (scene != null && !scenes.contains(scene)) {
            scenes.add(scene);
        }
    }
    
    /**
     * Remove a scene from the manager
     */
    public void removeScene(String name) {
        Scene scene = getScene(name);
        if (scene != null) {
            if (scene == currentScene) {
                unloadScene();
        }
            scenes.remove(scene);
        }
    }
    
    /**
     * Get a scene by name
     */
    public Scene getScene(String name) {
        for (Scene scene : scenes) {
            if (scene.getSceneName().equals(name)) {
                return scene;
            }
        }
        return null;
    }
    
    /**
     * Get the currently active scene
     */
    public Scene getActiveScene() {
        return currentScene;
    }
    
    /**
     * Start a specific scene by name (backward compatibility)
     */
    public void startScene(String sceneName) {
        loadScene(sceneName);
    }
    
    /**
     * Update the current scene
     */
    public void update() {
            if (currentScene != null && currentScene.isRunning()) {
            currentScene.update(0.016f); // Default delta time
        }
    }
    
    /**
     * Update with delta time (backward compatibility)
     */
    public void update(float deltaTime) {
        if (currentScene != null && currentScene.isRunning()) {
            currentScene.update(deltaTime);
        }
    }
    
    /**
     * Render the current scene
     */
    public void render(Object batch) {
        if (currentScene != null && currentScene.isRunning()) {
            currentScene.render();
        }
    }
    
    /**
     * Dispose of the scene manager
     */
    public void dispose() {
        unloadScene();
        scenes.clear();
    }
}
