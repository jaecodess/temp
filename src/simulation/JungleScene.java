package simulation;

import engine.managers.*;
import java.awt.Color;

/**
 * Level 3 — The Jungle.
 * Hardest level. Food is very fast and punishing. Dark lush jungle background.
 * No score threshold — survive as long as possible.
 */
public class JungleScene extends LevelScene {

    private static final LevelConfig CONFIG = new LevelConfig(
        3,
        "Level 3 — The Jungle",
        Integer.MAX_VALUE,          // final level, no advance
        135f,                       // good food speed
        175f,                       // bad food speed
        40f,                        // speed bump per quiz hit
        new Color(20, 55, 20),      // deep jungle green top
        new Color(10, 30, 15),      // near-black bottom
        new Color(30, 90, 30)       // accent: dim green grid
    );

    public JungleScene(EntityManager em, MovementManager mm,
                       CollisionManager cm, InputOutputManager io) {
        super("Level3", em, mm, cm, io);
    }

    @Override
    public LevelConfig getConfig() { return CONFIG; }
}
