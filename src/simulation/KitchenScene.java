package simulation;

import engine.managers.*;
import java.awt.Color;

/**
 * Level 1 — The Kitchen.
 * Calm starting level. Food moves slowly. Warm pastel background.
 */
public class KitchenScene extends LevelScene {

    private static final LevelConfig CONFIG = new LevelConfig(
        1,
        "Level 1 — The Kitchen",
        10,                         // advance at 10 points
        55f,                        // good food speed
        75f,                        // bad food speed
        20f,                        // speed bump per quiz hit
        new Color(255, 240, 210),   // warm cream top
        new Color(235, 190, 140),   // warm tan bottom
        new Color(200, 160, 100)    // accent: golden grid lines
    );

    public KitchenScene(EntityManager em, MovementManager mm,
                        CollisionManager cm, InputOutputManager io) {
        super("Level1", em, mm, cm, io);
    }

    @Override
    public LevelConfig getConfig() { return CONFIG; }
}
