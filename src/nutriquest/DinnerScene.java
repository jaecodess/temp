package nutriquest;

import engine.managers.*;

/**
 * Dinner level - dinner-themed foods.
 */
public class DinnerScene extends NutriQuestScene {
    public DinnerScene(EntityManager em, MovementManager mm, CollisionManager cm,
                      InputOutputManager io) {
        super("Dinner", em, mm, cm, io);
        setLevelDuration(90.0f);
    }

    @Override
    protected String[] getFoodSpawns() {
        return new String[]{"broccoli", "carrot", "rice", "apple", "banana",
                "burger", "donut", "chips", "soda", "broccoli", "rice", "carrot"};
    }
}
