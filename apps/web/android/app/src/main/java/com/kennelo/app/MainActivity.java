package com.kennelo.app;

import android.graphics.Color;
import android.os.Bundle;
import android.view.WindowInsetsController;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        applyLightStatusBar();
    }

    @Override
    public void onResume() {
        super.onResume();
        applyLightStatusBar();
    }

    @Override
    public void onWindowFocusChanged(boolean hasFocus) {
        super.onWindowFocusChanged(hasFocus);
        if (hasFocus) applyLightStatusBar();
    }

    private void applyLightStatusBar() {
        getWindow().setStatusBarColor(Color.TRANSPARENT);
        WindowInsetsController controller = getWindow().getInsetsController();
        if (controller != null) {
            controller.setSystemBarsAppearance(
                WindowInsetsController.APPEARANCE_LIGHT_STATUS_BARS,
                WindowInsetsController.APPEARANCE_LIGHT_STATUS_BARS
            );
        }
    }
}
