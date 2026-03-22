package nutriquest;

import engine.managers.*;

/**
 * Breakfast level - cereal, fruits, milk-themed foods.
 * Adding a new level = one new scene class.
 */
public class BreakfastScene extends NutriQuestScene {
    public BreakfastScene(EntityManager em, MovementManager mm, CollisionManager cm,
                         InputOutputManager io) {
        super("Breakfast", em, mm, cm, io);
        setLevelDuration(60.0f);
    }

    @Override
    protected String[] getFoodSpawns() {
        return new String[]{"apple", "banana", "rice", "donut", "burger",
                "apple", "carrot", "chips", "banana", "soda"};
    }
}
