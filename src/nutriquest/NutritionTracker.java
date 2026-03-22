package nutriquest;

/**
 * Observer pattern: Listens to food collection events.
 * Updates score and health bar when player collects/contacts food.
 */
public class NutritionTracker {
    private int score;
    private float health;
    private float maxHealth;
    private float slowDuration; // When hit by red food, player is slowed

    public NutritionTracker() {
        this.score = 0;
        this.maxHealth = 100f;
        this.health = maxHealth;
        this.slowDuration = 0;
    }

    /**
     * Called when player collects or contacts food (from collision handler).
     */
    public void onFoodCollected(Food food) {
        if (food == null || !food.isActive()) return;

        if (food.isHealthy()) {
            score += food.getPointValue();
            health = Math.min(maxHealth, health + 5);
        } else {
            // Red/junk food: reduce health, apply slow
            health -= 25;
            slowDuration = 2.0f;
        }
    }

    public void update(float deltaTime) {
        if (slowDuration > 0) {
            slowDuration -= deltaTime;
        }
    }

    public int getScore() { return score; }
    public float getHealth() { return health; }
    public float getMaxHealth() { return maxHealth; }
    public boolean isSlowed() { return slowDuration > 0; }
    public float getSlowDuration() { return slowDuration; }
    public boolean isDead() { return health <= 0; }

    public void reset() {
        score = 0;
        health = maxHealth;
        slowDuration = 0;
    }
}
