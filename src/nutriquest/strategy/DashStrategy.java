package nutriquest.strategy;

import engine.entities.Entity;

/**
 * DashStrategy: Fast bursts of movement. Harder to avoid.
 * Used for junk food (red) - faster and more challenging.
 */
public class DashStrategy implements MovementStrategy {
    private float timer;
    private float dashInterval;
    private float dashDuration;
    private float dashSpeed;
    private float velX;
    private float velY;
    private boolean isDashing;

    public DashStrategy() {
        this.timer = 0;
        this.dashInterval = 1.5f;
        this.dashDuration = 0.4f;
        this.dashSpeed = 120.0f;
        this.velX = 0;
        this.velY = 0;
        this.isDashing = false;
    }

    public DashStrategy(float dashSpeed) {
        this();
        this.dashSpeed = dashSpeed;
    }

    @Override
    public void update(Entity entity, float deltaTime, float centerX, float centerY,
                       float boundsWidth, float boundsHeight) {
        timer += deltaTime;

        if (!isDashing) {
            if (timer >= dashInterval) {
                timer = 0;
                isDashing = true;
                float angle = (float) (Math.random() * Math.PI * 2);
                velX = (float) Math.cos(angle) * dashSpeed;
                velY = (float) Math.sin(angle) * dashSpeed;
            }
        } else {
            if (timer >= dashDuration) {
                timer = 0;
                isDashing = false;
                velX = 0;
                velY = 0;
            }
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
