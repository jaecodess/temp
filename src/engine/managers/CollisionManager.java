package engine.managers;

import engine.collision.*;
import engine.entities.Entity;
import engine.utils.Vector2;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Manages collision detection and response
 * Coordinates between detection and handling systems
 */
public class CollisionManager {
    private EntityManager entityManager;
    private Map<String, Object> collisionHandlers;
    private List<ICollisionListener> listeners;
    private HandleCollision handleCollision;
    private DetectCollision detectCollision;
    private Map<Entity, String> entityLayers;
    
    public CollisionManager(EntityManager entityManager) {
        this.entityManager = entityManager;
        this.collisionHandlers = new HashMap<>();
        this.listeners = new ArrayList<>();
        this.handleCollision = new HandleCollision();
        this.detectCollision = new DetectCollision();
        this.entityLayers = new HashMap<>();
        
        initializeDefaultLayers();
    }
    
    /**
     * Initialize default collision layers
     */
    private void initializeDefaultLayers() {
        // Generic: allow all collisions by default
        // Specific layer collision rules should be configured by the game/simulation
        detectCollision.setDefault(true);
    }
    
    /**
     * Add a collision listener
     */
    public void addListener(ICollisionListener listener) {
        if (!listeners.contains(listener)) {
            listeners.add(listener);
        }
    }
    
    /**
     * Remove a collision listener
     */
    public void removeListener(ICollisionListener listener) {
        listeners.remove(listener);
    }
    
    /**
     * Set entity's collision layer
     */
    public void setEntityLayer(Entity entity, String layer) {
        entityLayers.put(entity, layer);
    }
    
    /**
     * Get entity's collision layer
     */
    public String getEntityLayer(Entity entity) {
        return entityLayers.getOrDefault(entity, "default");
    }
    
    /**
     * Detect collisions
     */
    public void detectCollision() {
        detectCollisions();
    }
    
    /**
     * Detect all collisions between active entities
     */
    public void detectCollisions() {
        List<Entity> activeEntities = entityManager.getActiveEntities();
        
        // Check each pair of entities
        for (int i = 0; i < activeEntities.size(); i++) {
            Entity a = activeEntities.get(i);
            String layerA = getEntityLayer(a);
            
            for (int j = i + 1; j < activeEntities.size(); j++) {
                Entity b = activeEntities.get(j);
                String layerB = getEntityLayer(b);
                
                // Check if these layers should detect collisions
                if (detectCollision.shouldDetect(layerA, layerB)) {
                    if (checkCollision(a, b)) {
                        handleCollision(a, b, layerA, layerB);
                    }
                }
            }
        }
    }
    
    /**
     * Check if two entities are colliding (AABB collision)
     */
    private boolean checkCollision(Entity a, Entity b) {
        // Axis-Aligned Bounding Box collision detection
        float aLeft = a.getX() - a.getWidth() / 2;
        float aRight = a.getX() + a.getWidth() / 2;
        float aTop = a.getY() - a.getHeight() / 2;
        float aBottom = a.getY() + a.getHeight() / 2;
        
        float bLeft = b.getX() - b.getWidth() / 2;
        float bRight = b.getX() + b.getWidth() / 2;
        float bTop = b.getY() - b.getHeight() / 2;
        float bBottom = b.getY() + b.getHeight() / 2;
        
        return aLeft < bRight && aRight > bLeft &&
               aTop < bBottom && aBottom > bTop;
    }
    
    /**
     * Check if two entities are colliding (Circle collision)
     * Reserved for future use when circular collision detection is needed
     */
    @SuppressWarnings("unused")
    private boolean checkCircleCollision(Entity a, Entity b) {
        Vector2 posA = a.getPosition();
        Vector2 posB = b.getPosition();
        float distance = posA.distance(posB);
        return distance < (a.getRadius() + b.getRadius());
    }
    
    /**
     * Handle collision between two entities
     */
    public void handleCollision(Entity a, Entity b, String layerA, String layerB) {
        // Notify specific handler for these layers
        handleCollision.handleCollision(a, b, layerA, layerB);
        
        // Notify all general listeners
        for (ICollisionListener listener : listeners) {
            listener.onCollision(a, b);
        }
    }
    
    /**
     * Register a collision handler
     */
    public void registerCollision(String layerA, String layerB, ICollisionListener handler) {
        registerHandler(layerA, layerB, handler);
        collisionHandlers.put(layerA + "_" + layerB, handler);
    }
    
    /**
     * Remove a collision handler
     */
    public void removeCollision(String layerA, String layerB) {
        handleCollision.remove(layerA, layerB);
        collisionHandlers.remove(layerA + "_" + layerB);
    }
    
    /**
     * Register a collision handler for specific layers (backward compatibility)
     */
    public void registerHandler(String layerA, String layerB, ICollisionListener handler) {
        handleCollision.register(layerA, layerB, handler);
        collisionHandlers.put(layerA + "_" + layerB, handler);
    }
    
    /**
     * Configure collision detection between layers
     */
    public void setLayerCollision(String layerA, String layerB, boolean detect) {
        detectCollision.set(layerA, layerB, detect);
    }
    
    /**
     * Add an entity to collision system with layer
     */
    public void addCollidable(Entity entity, String layer) {
        setEntityLayer(entity, layer);
    }
    
    /**
     * Remove an entity from collision system
     */
    public void removeCollidable(Entity entity) {
        entityLayers.remove(entity);
    }
    
    /**
     * Get collision handler system
     */
    public HandleCollision getHandleCollision() {
        return handleCollision;
    }
    
    /**
     * Get collision detection system
     */
    public DetectCollision getDetectCollision() {
        return detectCollision;
    }
    
    /**
     * Clear all collision handlers and entity layers
     */
    public void clearAll() {
        collisionHandlers.clear();
        listeners.clear();
        entityLayers.clear();
        handleCollision = new HandleCollision();
        System.out.println("CollisionManager: Cleared all handlers and layers");
    }
}
