package engine.entities;

import engine.utils.Vector2;
import java.util.ArrayList;
import java.util.List;

/**
 * Abstract base class for all entities in the engine
 * Provides common functionality for game objects
 */
public abstract class Entity implements Renderable {
    protected String state;
    protected List<EntityComponent> components;
    protected float x;
    protected float y;
    protected boolean active;
    protected float speed;
    protected float width;
    protected float length;
    protected float radius;
    protected Vector2 position;
    protected Vector2 velocity;
    protected Object texture;
    
    public Entity(float x, float y) {
        this.x = x;
        this.y = y;
        this.position = new Vector2(x, y);
        this.velocity = new Vector2(0, 0);
        this.components = new ArrayList<>();
        this.active = true;
        this.state = "idle";
        this.speed = 1.0f;
        this.width = 32;
        this.length = 32;
        this.radius = 16;
        this.texture = null;
    }
    
    // Position getters and setters
    public float getX() {
        return x;
    }
    
    public void setX(float x) {
        this.x = x;
        this.position.x = x;
    }
    
    public float getY() {
        return y;
    }
    
    public void setY(float y) {
        this.y = y;
        this.position.y = y;
    }
    
    public void setPosition(float x, float y) {
        this.x = x;
        this.y = y;
        this.position.set(x, y);
    }
    
    public Vector2 getPosition() {
        return new Vector2(position);
    }
    
    // Active state
    public boolean isActive() {
        return active;
    }
    
    public void setActive(boolean active) {
        this.active = active;
    }
    
    // Speed
    public float getSpeed() {
        return speed;
    }
    
    public void setSpeed(float speed) {
        this.speed = speed;
    }
    
    // State
    public String getState() {
        return state;
    }
    
    public void setState(String state) {
        this.state = state;
    }
    
    // Dimensions
    public float getWidth() {
        return width;
    }
    
    public void setWidth(float width) {
        this.width = width;
    }
    
    public float getLength() {
        return length;
    }
    
    public void setLength(float length) {
        this.length = length;
    }
    
    // Keep height for backward compatibility
    public float getHeight() {
        return length;
    }
    
    public void setHeight(float height) {
        this.length = height;
    }
    
    public float getRadius() {
        return radius;
    }
    
    public void setRadius(float radius) {
        this.radius = radius;
    }
    
    // Component system
    public void addComponent(String type, EntityComponent component) {
        if (component != null) {
            components.add(component);
        }
    }
    
    public EntityComponent getComponent(String type) {
        for (EntityComponent comp : components) {
            if (comp.getType().equals(type)) {
                return comp;
            }
        }
        return null;
    }
    
    public void removeComponent(String type) {
        components.removeIf(comp -> comp.getType().equals(type));
    }
    
    public boolean hasComponent(String type) {
        return getComponent(type) != null;
    }
    
    // Position helper methods
    public Vector2 getPos() {
        return getPosition();
    }
    
    public Vector2 getVelocity() {
        return velocity;
    }
    
    public void setPos(float x, float y) {
        setPosition(x, y);
    }
    
    public void handleInput() {
        // Override in subclasses
    }
    
    public boolean shouldBeRemoved() {
        return !active;
    }
    
    /**
     * Reset entity to initial position
     */
    public abstract void resetPosition();
    
    /**
     * Check if entity is out of bounds
     */
    public boolean outOfBounds(float minX, float minY, float maxX, float maxY) {
        return x < minX || x > maxX || y < minY || y > maxY;
    }
    
    /**
     * Update entity state
     */
    public abstract void update(float deltaTime);
    
    /**
     * Render the entity
     */
    @Override
    public abstract void render();
    
    /**
     * Clean up entity resources
     */
    public void dispose() {
        components.clear();
    }
    
    @Override
    public String toString() {
        return String.format("%s[x=%.2f, y=%.2f, state=%s, active=%b]", 
            getClass().getSimpleName(), x, y, state, active);
    }
}
