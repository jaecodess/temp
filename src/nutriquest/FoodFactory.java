package nutriquest;

import nutriquest.strategy.*;

/**
 * Factory pattern: Creates configured Food entities with nutrition data, speed, and point value.
 * Adding a new food (e.g. broccoli) requires only one new case/config.
 */
public class FoodFactory {
    /**
     * Create a food entity by ID. Returns fully configured entity with nutrition, speed, points.
     * @param foodId e.g. "apple", "broccoli", "burger", "donut"
     * @param x Spawn X
     * @param y Spawn Y
     * @return Configured Food entity, or null if unknown
     */
    public static Food create(String foodId, float x, float y) {
        switch (foodId.toLowerCase()) {
            case "apple":
                return new Food(x, y, "apple", Food.TYPE_GREEN, "Fruits", 10, 40f,
                        new FloatStrategy(35f));
            case "banana":
                return new Food(x, y, "banana", Food.TYPE_GREEN, "Fruits", 10, 45f,
                        new FloatStrategy(40f));
            case "broccoli":
                return new Food(x, y, "broccoli", Food.TYPE_GREEN, "Vegetables", 15, 30f,
                        new CircleStrategy(1.0f, 60f));
            case "carrot":
                return new Food(x, y, "carrot", Food.TYPE_GREEN, "Vegetables", 12, 38f,
                        new FloatStrategy(38f));
            case "rice":
                return new Food(x, y, "rice", Food.TYPE_GREEN, "Grains", 8, 35f,
                        new CircleStrategy(0.8f, 70f));
            case "burger":
                return new Food(x, y, "burger", Food.TYPE_RED, "Junk", -1, 80f,
                        new DashStrategy(130f));
            case "donut":
                return new Food(x, y, "donut", Food.TYPE_RED, "Junk", -1, 90f,
                        new DashStrategy(140f));
            case "chips":
                return new Food(x, y, "chips", Food.TYPE_RED, "Junk", -1, 85f,
                        new DashStrategy(125f));
            case "soda":
                return new Food(x, y, "soda", Food.TYPE_RED, "Junk", -1, 95f,
                        new DashStrategy(150f));
            default:
                return new Food(x, y, foodId, Food.TYPE_GREEN, "Other", 5, 40f,
                        new FloatStrategy(40f));
        }
    }
}
