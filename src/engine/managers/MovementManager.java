package engine.managers;

import engine.entities.Entity;
import engine.entities.IMovable;
import engine.input.InputAction;
import engine.utils.Vector2;
import java.util.ArrayList;
import java.util.List;

/**
 * Manages movement for entities
 * Handles input-based movement and velocity
 */
public class MovementManager implements IMovable {
    private List<Entity> entity;
    private int moveType;
    private InputOutputManager inputManager;
    private float moveSpeed;
    
    public MovementManager(InputOutputManager inputManager) {
        this.entity = new ArrayList<>();
        this.inputManager = inputManager;
        this.moveSpeed = 100.0f; // default movement speed
        this.moveType = 0; // Default move type
    }
    
    /**
     * Register an entity for movement management
     */
    public void addEntity(Entity entity) {
        if (!this.entity.contains(entity)) {
            this.entity.add(entity);
        }
    }
    
    /**
     * Unregister an entity from movement management
     */
    public void removeEntity(Entity entity) {
        this.entity.remove(entity);
    }
    
    /**
     * Move an entity
     */
    public void move(Entity entity) {
        if (entity != null && entity.isActive()) {
            processMovement(entity, 0.016f);
        }
    }
    
    /**
     * Update movement for all entities
     */
    public void update() {
        float deltaTime = 0.016f; // Default delta time
        for (Entity e : entity) {
            if (e.isActive()) {
                processMovement(e, deltaTime);
            }
        }
    }
    
    /**
     * Update with delta time (backward compatibility)
     */
    public void update(float deltaTime) {
        for (Entity e : entity) {
            if (e.isActive()) {
                processMovement(e, deltaTime);
            }
        }
    }
    
    /**
     * Process movement for a specific entity
     */
    private void processMovement(Entity entity, float deltaTime) {
        Vector2 direction = new Vector2(0, 0);
        
        // Check input actions
        if (inputManager.isPressed(InputAction.MOVE_UP)) {
            direction.y -= 1;
        }
        if (inputManager.isPressed(InputAction.MOVE_DOWN)) {
            direction.y += 1;
        }
        if (inputManager.isPressed(InputAction.MOVE_LEFT)) {
            direction.x -= 1;
        }
        if (inputManager.isPressed(InputAction.MOVE_RIGHT)) {
            direction.x += 1;
        }
        
        // Normalize and apply movement
        if (direction.length() > 0) {
            direction = direction.normalize();
            float speed = entity.getSpeed() > 0 ? entity.getSpeed() : moveSpeed;
            float dx = direction.x * speed * deltaTime;
            float dy = direction.y * speed * deltaTime;
            
            entity.setPosition(entity.getX() + dx, entity.getY() + dy);
            entity.setState("moving");
        } else if (entity.getState().equals("moving")) {
            entity.setState("idle");
        }
    }
    
    @Override
    public boolean isMoving(Entity entity) {
        if (entity == null) return false;
        return entity.getState().equals("moving");
    }
    
    @Override
    public void moveLeft() {
        for (Entity e : entity) {
            if (e.isActive()) {
                e.setX(e.getX() - moveSpeed * 0.016f);
            }
        }
    }
    
    @Override
    public void moveRight() {
        for (Entity e : entity) {
            if (e.isActive()) {
                e.setX(e.getX() + moveSpeed * 0.016f);
            }
        }
    }
    
    @Override
    public void moveUp() {
        for (Entity e : entity) {
            if (e.isActive()) {
                e.setY(e.getY() - moveSpeed * 0.016f);
            }
        }
    }
    
    @Override
    public void moveDown() {
        for (Entity e : entity) {
            if (e.isActive()) {
                e.setY(e.getY() + moveSpeed * 0.016f);
            }
        }
    }
    
    /**
     * Move a specific entity in a direction
     */
    public void moveEntity(Entity entity, float dx, float dy, float deltaTime) {
        if (entity != null && entity.isActive()) {
            float speed = entity.getSpeed() > 0 ? entity.getSpeed() : moveSpeed;
            entity.setPosition(
                entity.getX() + dx * speed * deltaTime,
                entity.getY() + dy * speed * deltaTime
            );
        }
    }
    
    public void setMoveSpeed(float speed) {
        this.moveSpeed = speed;
    }
    
    public float getMoveSpeed() {
        return moveSpeed;
    }
    
    public List<Entity> getEntities() {
        return new ArrayList<>(entity);
    }
    
    public int getMoveType() {
        return moveType;
    }
    
    public void setMoveType(int moveType) {
        this.moveType = moveType;
    }
}
