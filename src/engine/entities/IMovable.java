package engine.entities;

/**
 * Interface for entities that can move
 * Defines movement capabilities
 */
public interface IMovable {
    /**
     * Check if entity is currently moving
     */
    boolean isMoving(Entity entity);
    
    /**
     * Move entity left
     */
    void moveLeft();
    
    /**
     * Move entity right
     */
    void moveRight();
    
    /**
     * Move entity up
     */
    void moveUp();
    
    /**
     * Move entity down
     */
    void moveDown();
}
