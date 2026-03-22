package nutriquest;

import engine.managers.*;

/**
 * Lunch level - varied meal foods.
 */
public class LunchScene extends NutriQuestScene {
    public LunchScene(EntityManager em, MovementManager mm, CollisionManager cm,
                     InputOutputManager io) {
        super("Lunch", em, mm, cm, io);
        setLevelDuration(75.0f);
    }

    @Override
    protected String[] getFoodSpawns() {
        return new String[]{"broccoli", "carrot", "rice", "burger", "chips",
                "apple", "donut", "banana", "soda", "broccoli", "carrot"};
    }
}
