package simulation;

import engine.managers.*;
import java.awt.Color;

/**
 * Level 2 — The Market.
 * Mid-game challenge. Food speeds up noticeably. Outdoor sky-blue background.
 */
public class MarketScene extends LevelScene {

    private static final LevelConfig CONFIG = new LevelConfig(
        2,
        "Level 2 — The Market",
        25,                         // advance at 25 points
        90f,                        // good food speed
        120f,                       // bad food speed
        30f,                        // speed bump per quiz hit
        new Color(135, 200, 235),   // sky blue top
        new Color(80, 160, 80),     // grass green bottom
        new Color(60, 130, 60)      // accent: dark green grid
    );

    public MarketScene(EntityManager em, MovementManager mm,
                       CollisionManager cm, InputOutputManager io) {
        super("Level2", em, mm, cm, io);
    }

    @Override
    public LevelConfig getConfig() { return CONFIG; }
}
