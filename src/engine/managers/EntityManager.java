package engine.managers;

import engine.entities.Entity;
import java.util.ArrayList;
import java.util.List;

/**
 * Manages all entities in the game
 * Handles entity lifecycle, updates, and rendering
 */
public class EntityManager {
    private List<Entity> entities;
    private List<Entity> entitiesToAdd;
    private List<Entity> entitiesToRemove;
    private String state;
    
    public EntityManager() {
        this.entities = new ArrayList<>();
        this.entitiesToAdd = new ArrayList<>();
        this.entitiesToRemove = new ArrayList<>();
        this.state = "active";
    }
    
    /**
     * Add an entity to be managed
     */
    public void addEntity(Entity entity) {
        if (entity != null && !entities.contains(entity)) {
            entitiesToAdd.add(entity);
        }
    }
    
    /**
     * Remove an entity from management
     */
    public void removeEntity(Entity entity) {
        if (entity != null && entities.contains(entity)) {
            entitiesToRemove.add(entity);
        }
    }
    
    /**
     * Process pending additions and removals
     */
    private void processPendingChanges() {
        // Add new entities
        if (!entitiesToAdd.isEmpty()) {
            entities.addAll(entitiesToAdd);
            entitiesToAdd.clear();
        }
        
        // Remove entities
        if (!entitiesToRemove.isEmpty()) {
            for (Entity entity : entitiesToRemove) {
                entity.dispose();
                entities.remove(entity);
            }
            entitiesToRemove.clear();
        }
    }
    
    /**
     * Update all entities
     */
    public void update() {
        processPendingChanges();
        
        // Update each active entity
        for (Entity entity : entities) {
            if (entity.isActive()) {
                entity.update(0.016f); // Default delta time
            }
        }
    }
    
    /**
     * Render all entities
     */
    public void render(Object batch) {
        for (Entity entity : entities) {
            if (entity.isActive()) {
                entity.render();
            }
        }
    }
    
    /**
     * Update all active entities (backward compatibility)
     */
    public void updateAll(float deltaTime) {
        processPendingChanges();
        for (Entity entity : entities) {
            if (entity.isActive()) {
                entity.update(deltaTime);
            }
        }
    }
    
    /**
     * Render all active entities (backward compatibility)
     */
    public void renderAll() {
        render(null);
    }
    
    /**
     * Get an entity by ID
     */
    public Entity getEntity(String id) {
        // Simple implementation - could use a Map for better performance
        for (Entity entity : entities) {
            if (entity.toString().contains(id) || String.valueOf(entity.hashCode()).equals(id)) {
                return entity;
            }
        }
        return null;
    }
    
    /**
     * Get all entities
     */
    public List<Entity> getEntities() {
        return new ArrayList<>(entities);
    }
    
    /**
     * Get active entities only
     */
    public List<Entity> getActiveEntities() {
        List<Entity> active = new ArrayList<>();
        for (Entity entity : entities) {
            if (entity.isActive()) {
                active.add(entity);
            }
        }
        return active;
    }
    
    /**
     * Get entity count
     */
    public int getEntityCount() {
        return entities.size();
    }
    
    /**
     * Get active entity count
     */
    public int getActiveEntityCount() {
        int count = 0;
        for (Entity entity : entities) {
            if (entity.isActive()) {
                count++;
            }
        }
        return count;
    }
    
    /**
     * Find entities by type
     */
    public <T extends Entity> List<T> getEntitiesByType(Class<T> type) {
        List<T> result = new ArrayList<>();
        for (Entity entity : entities) {
            if (type.isInstance(entity)) {
                result.add(type.cast(entity));
            }
        }
        return result;
    }
    
    /**
     * Clear all entities
     */
    public void clear() {
        for (Entity entity : entities) {
            entity.dispose();
        }
        entities.clear();
        entitiesToAdd.clear();
        entitiesToRemove.clear();
    }
    
    /**
     * Dispose of all resources
     */
    public void dispose() {
        clear();
    }
    
    public String getState() {
        return state;
    }
    
    public void setState(String state) {
        this.state = state;
    }
}
