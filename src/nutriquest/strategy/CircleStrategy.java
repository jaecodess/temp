package nutriquest.strategy;

import engine.entities.Entity;

/**
 * CircleStrategy: Food orbits around a center point.
 * Used for foods that move in predictable patterns.
 */
public class CircleStrategy implements MovementStrategy {
    private float angle;
    private float orbitSpeed;
    private float orbitRadius;

    public CircleStrategy() {
        this.angle = (float) (Math.random() * Math.PI * 2);
        this.orbitSpeed = 1.2f;
        this.orbitRadius = 80.0f;
    }

    public CircleStrategy(float orbitSpeed, float orbitRadius) {
        this();
        this.orbitSpeed = orbitSpeed;
        this.orbitRadius = orbitRadius;
    }

    @Override
    public void update(Entity entity, float deltaTime, float centerX, float centerY,
                       float boundsWidth, float boundsHeight) {
        angle += orbitSpeed * deltaTime;
        float newX = centerX + (float) Math.cos(angle) * orbitRadius;
        float newY = centerY + (float) Math.sin(angle) * orbitRadius;

        float hw = entity.getWidth() / 2;
        float hh = entity.getHeight() / 2;
        newX = Math.max(hw, Math.min(boundsWidth - hw, newX));
        newY = Math.max(hh, Math.min(boundsHeight - hh, newY));

        entity.setPosition(newX, newY);
    }
}
