package simulation;

import engine.entities.NPC;
import engine.entities.Entity;

/**
 * Food item entity.
 * Good food bounces freely around the full screen (billiard-ball style).
 * Bad food homes toward the player with a slight random wobble.
 *
 * Overrides NPC.update() entirely — the NPC patrol/wander radii are too
 * restrictive for a full-screen game.
 */
public class FoodItem extends NPC {

    private final String  foodName;
    private final boolean goodFood;

    // Full-screen bounds (set by game before first update)
    private float worldW = 800f;
    private float worldH = 600f;
    private static final float HUD_TOP = 55f;  // HUD bar height

    // Velocity (pixels/sec) — initialised to a random direction on first update
    private float vx, vy;
    private boolean velocityInitialised = false;

    // Reference to the player — no longer used for homing, kept for API compat
    private Entity playerRef = null;

    // Direction-change timer for bad food random steering
    private float steerTimer = 0f;
    private float steerInterval = 1.8f; // seconds between random direction nudges

    // ── Construction ─────────────────────────────────────────────────
    public FoodItem(float x, float y, String behavior, String foodName, boolean goodFood) {
        super(x, y, behavior);
        this.foodName = foodName;
        this.goodFood = goodFood;
    }

    // ── Accessors ────────────────────────────────────────────────────
    public String  getFoodName() { return foodName; }
    public boolean isGoodFood()  { return goodFood;  }

    public void setWorldBounds(float w, float h) { worldW = w; worldH = h; }
    public void setPlayerRef(Entity p)           { playerRef = p; }

    // ── Update ───────────────────────────────────────────────────────
    @Override
    public void update(float dt) {
        if (!active) return;

        if (!velocityInitialised) {
            initVelocity();
        }

        if (goodFood) {
            updateBounce(dt);
        } else {
            updateRandomBounce(dt);
        }

        // Clamp inside screen
        float hw = width  / 2f;
        float hh = length / 2f;
        if (x < hw)         { x = hw;         vx =  Math.abs(vx); }
        if (x > worldW - hw){ x = worldW - hw; vx = -Math.abs(vx); }
        if (y < HUD_TOP + hh){ y = HUD_TOP + hh; vy =  Math.abs(vy); }
        if (y > worldH - hh){ y = worldH - hh; vy = -Math.abs(vy); }

        position.set(x, y);
    }

    // ── Bounce movement (good food) ──────────────────────────────────
    private void updateBounce(float dt) {
        x += vx * dt;
        y += vy * dt;
    }

    // ── Random steering bounce (bad food) ────────────────────────────
    // Moves freely around the whole field, randomly nudging direction
    // every ~1.8 seconds so it feels alive but never chases the player.
    private void updateRandomBounce(float dt) {
        steerTimer += dt;
        if (steerTimer >= steerInterval) {
            steerTimer = 0f;
            // Vary the interval slightly so multiple bad foods desync
            steerInterval = 1.2f + (float)(Math.random() * 1.2f);
            // Pick a new random direction but keep the same speed
            double angle = Math.random() * 2 * Math.PI;
            vx = (float)(Math.cos(angle) * speed);
            vy = (float)(Math.sin(angle) * speed);
        }
        x += vx * dt;
        y += vy * dt;
    }

    // ── Velocity initialisation ──────────────────────────────────────
    private void initVelocity() {
        // Random outward direction, consistent speed
        double angle = Math.random() * 2 * Math.PI;
        vx = (float)(Math.cos(angle) * speed);
        vy = (float)(Math.sin(angle) * speed);
        velocityInitialised = true;
    }

    /** Called when speed changes so velocity magnitude stays consistent. */
    @Override
    public void setSpeed(float newSpeed) {
        if (velocityInitialised && this.speed > 0) {
            float scale = newSpeed / this.speed;
            vx *= scale;
            vy *= scale;
        }
        super.setSpeed(newSpeed);
    }
}
