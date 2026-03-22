package engine.collision;

import engine.entities.Entity;

/**
 * Interface for collision event listeners
 */
public interface ICollisionListener {
    /**
     * Called when a collision occurs between two entities
     * @param a First entity in collision
     * @param b Second entity in collision
     */
    void onCollision(Entity a, Entity b);
}
