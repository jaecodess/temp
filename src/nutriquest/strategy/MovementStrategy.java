package nutriquest.strategy;

import engine.entities.Entity;
import engine.utils.Vector2;

/**
 * Strategy pattern: defines movement behavior for food NPCs.
 * Different strategies: Float (drifts slowly), Dash (fast bursts), Circle (orbits).
 */
public interface MovementStrategy {
    /**
     * Update the entity's position based on this movement strategy.
     * @param entity The food entity to move
     * @param deltaTime Time since last update
     * @param centerX Center X for circular/patrol behavior
     * @param centerY Center Y for circular/patrol behavior
     * @param boundsWidth World width for boundary checks
     * @param boundsHeight World height for boundary checks
     */
    void update(Entity entity, float deltaTime, float centerX, float centerY,
                float boundsWidth, float boundsHeight);
}
