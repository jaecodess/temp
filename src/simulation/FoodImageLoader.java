package simulation;

import javax.imageio.ImageIO;
import java.awt.*;
import java.awt.image.BufferedImage;
import java.io.*;
import java.util.HashMap;
import java.util.Map;

/**
 * Loads and caches food images from assets/food/ folder.
 * Place PNG images named after the food (e.g. apple.png, banana.png) in assets/food/
 */
public class FoodImageLoader {
    private static final Map<String, Image> cache = new HashMap<>();
    private static final String[] SEARCH_PATHS = {
        "assets/food/",
        "assets/",
        "AbstractEngine/assets/food/",
        "AbstractEngine/assets/"
    };

    public static Image getFoodImage(String foodName) {
        if (foodName == null) return null;
        String key = foodName.toLowerCase().trim();
        if (cache.containsKey(key)) {
            return cache.get(key);
        }
        Image img = loadImage(key);
        cache.put(key, img);
        return img;
    }

    private static Image loadImage(String name) {
        String filename = name + ".png";
        for (String base : SEARCH_PATHS) {
            try {
                File f = new File(base + filename);
                if (f.exists()) {
                    BufferedImage img = ImageIO.read(f);
                    if (img != null) return img;
                }
            } catch (IOException ignored) { }
        }
        try (InputStream in = FoodImageLoader.class.getResourceAsStream("/assets/food/" + filename)) {
            if (in != null) {
                return ImageIO.read(in);
            }
        } catch (IOException ignored) { }
        return null;
    }
}
