package nutriquest;

import engine.entities.Entity;
import engine.utils.Vector2;
import nutriquest.strategy.MovementStrategy;

/**
 * Food entity - represents collectible/avoidable food items in NutriQuest.
 * Green foods (fruits, vegetables) give points. Red foods (junk) slow or end game.
 * Uses Strategy pattern for movement behavior.
 */
public class Food extends Entity {
    public static final String TYPE_GREEN = "green";
    public static final String TYPE_RED = "red";

    private String foodId;
    private String foodType;
    private String foodGroup;
    private int pointValue;
    private MovementStrategy movementStrategy;
    private float centerX;
    private float centerY;
    private float worldWidth;
    private float worldHeight;

    public Food(float x, float y, String foodId, String foodType, String foodGroup,
                int pointValue, float speed, MovementStrategy strategy) {
        super(x, y);
        this.foodId = foodId;
        this.foodType = foodType;
        this.foodGroup = foodGroup;
        this.pointValue = pointValue;
        this.movementStrategy = strategy;
        this.centerX = x;
        this.centerY = y;
        this.worldWidth = 800;
        this.worldHeight = 600;
        this.speed = speed;
        this.width = 36;
        this.length = 36;
    }

    public void setWorldBounds(float width, float height) {
        this.worldWidth = width;
        this.worldHeight = height;
    }

    public void setMovementStrategy(MovementStrategy strategy) {
        this.movementStrategy = strategy;
    }

    @Override
    public void update(float deltaTime) {
        if (!active || movementStrategy == null) return;
        movementStrategy.update(this, deltaTime, centerX, centerY, worldWidth, worldHeight);
        position.set(x, y);
    }

    @Override
    public void resetPosition() {
        setPosition(centerX, centerY);
    }

    @Override
    public void render() {
        if (!active) return;
        System.out.println(String.format("Rendering Food[%s,%s] at (%.1f, %.1f) pts=%d",
                foodId, foodType, x, y, pointValue));
    }

    public String getFoodId() { return foodId; }
    public String getFoodType() { return foodType; }
    public String getFoodGroup() { return foodGroup; }
    public int getPointValue() { return pointValue; }
    public boolean isHealthy() { return TYPE_GREEN.equals(foodType); }
}
