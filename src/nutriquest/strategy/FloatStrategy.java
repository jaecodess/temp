package nutriquest.strategy;

import engine.entities.Entity;

/**
 * FloatStrategy: Food drifts slowly in random directions.
 * Used for gentle/healthy foods like fruits.
 */
public class FloatStrategy implements MovementStrategy {
    private float timer;
    private float directionChangeInterval;
    private float driftSpeed;
    private float velX;
    private float velY;

    public FloatStrategy() {
        this.timer = 0;
        this.directionChangeInterval = 2.0f;
        this.driftSpeed = 40.0f;
        this.velX = 0;
        this.velY = 0;
    }

    public FloatStrategy(float driftSpeed) {
        this();
        this.driftSpeed = driftSpeed;
    }

    @Override
    public void update(Entity entity, float deltaTime, float centerX, float centerY,
                       float boundsWidth, float boundsHeight) {
        timer += deltaTime;
        if (timer >= directionChangeInterval) {
            timer = 0;
            float angle = (float) (Math.random() * Math.PI * 2);
            velX = (float) Math.cos(angle) * driftSpeed;
            velY = (float) Math.sin(angle) * driftSpeed;
        }

        float newX = entity.getX() + velX * deltaTime;
        float newY = entity.getY() + velY * deltaTime;

        float hw = entity.getWidth() / 2;
        float hh = entity.getHeight() / 2;
        newX = Math.max(hw, Math.min(boundsWidth - hw, newX));
        newY = Math.max(hh, Math.min(boundsHeight - hh, newY));

        entity.setPosition(newX, newY);
    }
}
