package engine.collision;

import engine.entities.Entity;
import java.util.HashMap;
import java.util.Map;

/**
 * Handles collision responses between different layers
 * Stores and invokes collision handlers for layer pairs
 */
public class HandleCollision {
    private Map<String, Map<String, ICollisionListener>> handlers;
    private Object bounds;
    private boolean isTrigger;
    private String name;
    
    public HandleCollision() {
        this.handlers = new HashMap<>();
        this.bounds = null;
        this.isTrigger = false;
        this.name = "";
    }
    
    /**
     * Register a collision handler for two layers
     */
    public void register(String layerA, String layerB, ICollisionListener handler) {
        if (!handlers.containsKey(layerA)) {
            handlers.put(layerA, new HashMap<>());
        }
        handlers.get(layerA).put(layerB, handler);
        
        // Register reverse direction as well
        if (!handlers.containsKey(layerB)) {
            handlers.put(layerB, new HashMap<>());
        }
        handlers.get(layerB).put(layerA, handler);
    }
    
    /**
     * Get collision handler for two layers
     */
    public ICollisionListener get(String layerA, String layerB) {
        if (handlers.containsKey(layerA)) {
            return handlers.get(layerA).get(layerB);
        }
        return null;
    }
    
    /**
     * Check if handler exists for two layers
     */
    public boolean has(String layerA, String layerB) {
        return handlers.containsKey(layerA) && 
               handlers.get(layerA).containsKey(layerB);
    }
    
    /**
     * Remove collision handler for two layers
     */
    public void remove(String layerA, String layerB) {
        if (handlers.containsKey(layerA)) {
            handlers.get(layerA).remove(layerB);
        }
        if (handlers.containsKey(layerB)) {
            handlers.get(layerB).remove(layerA);
        }
    }
    
    /**
     * Handle collision between two entities
     */
    public void handleCollision(Entity a, Entity b, String layerA, String layerB) {
        ICollisionListener handler = get(layerA, layerB);
        if (handler != null) {
            handler.onCollision(a, b);
        }
    }
    
    /**
     * Clear all handlers
     */
    public void clear() {
        handlers.clear();
    }
    
    /**
     * Register a collision layer
     */
    public void registerLayer(String layer, String layerId, ICollisionListener handler) {
        register(layer, layerId, handler);
    }
    
    /**
     * Get a collision layer handler
     */
    public ICollisionListener getLayer(String layer, String layerId) {
        return get(layer, layerId);
    }
    
    /**
     * Remove a collision layer
     */
    public void removeLayer(String layer, String layerId) {
        remove(layer, layerId);
    }
    
    // Getters and setters
    public Object getBounds() {
        return bounds;
    }
    
    public void setBounds(Object bounds) {
        this.bounds = bounds;
    }
    
    public boolean isTrigger() {
        return isTrigger;
    }
    
    public void setTrigger(boolean isTrigger) {
        this.isTrigger = isTrigger;
    }
    
    public String getName() {
        return name;
    }
    
    public void setName(String name) {
        this.name = name;
    }
}
