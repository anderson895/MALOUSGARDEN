package com.wheic.arapp;

import androidx.appcompat.app.AppCompatActivity;
import androidx.cardview.widget.CardView;
import android.app.Activity;
import android.app.ActivityManager;
import android.app.AlertDialog;
import android.content.Context;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.view.View;
import android.widget.Toast;
import com.google.ar.core.Anchor;
import com.google.ar.sceneform.AnchorNode;
import com.google.ar.sceneform.rendering.ModelRenderable;
import com.google.ar.sceneform.ux.ArFragment;
import com.google.ar.sceneform.ux.TransformableNode;
import java.util.Objects;

public class ARActivity extends AppCompatActivity {

    private ArFragment arCam;
    String Model;
    CardView cardViewAglaonema, cardViewAreca, cardViewCaladium, cardViewPebble;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.ar_activity);

        cardViewAglaonema = findViewById(R.id.cardViewAglaonema);
        cardViewAreca = findViewById(R.id.cardViewAreca);
        cardViewCaladium = findViewById(R.id.cardViewCaladium);
        cardViewPebble = findViewById(R.id.cardViewPebble);

        /*Uri data = getIntent().getData();
        if (data != null) {
            Model = data.getQueryParameter("value");
        } else {
            //Log.d("DeepLink", "No data received");
        }*/

        if (checkSystemSupport(this))
        {

            arCam = (ArFragment) getSupportFragmentManager().findFragmentById(R.id.arCameraArea);
            arCam.setOnTapArPlaneListener((hitResult, plane, motionEvent) -> {

                if (Model == null) {
                    Toast.makeText(this, "Please select a model first", Toast.LENGTH_SHORT).show();
                    return;
                }

                Anchor anchor = hitResult.createAnchor();
                int modelResId = 0;

                switch (Model) {
                    case "aglaonema_plant":
                        modelResId = R.raw.aglaonema_plant;
                        break;
                    case "areca_palm":
                        modelResId = R.raw.areca_palm;
                        break;
                    case "caladium_plant":
                        modelResId = R.raw.caladium_plant;
                        break;
                    case "pebbles":
                        modelResId = R.raw.pebbles;
                        break;
                }

                if (modelResId != 0) {
                    ModelRenderable.builder()
                            .setSource(this, modelResId)
                            .setIsFilamentGltf(true)
                            .build()
                            .thenAccept(modelRenderable -> addModel(anchor, modelRenderable))
                            .exceptionally(throwable -> {
                                new AlertDialog.Builder(this)
                                        .setMessage("Something went wrong: " + throwable.getMessage())
                                        .show();
                                return null;
                            });
                }
            });


        } else {
            return;
        }

        cardViewAglaonema.setOnClickListener(v -> Model = "aglaonema_plant");
        cardViewAreca.setOnClickListener(v -> Model = "areca_palm");
        cardViewCaladium.setOnClickListener(v -> Model = "caladium_plant");
        cardViewPebble.setOnClickListener(v -> Model = "pebbles");
    }

    private void addModel(Anchor anchor, ModelRenderable modelRenderable) {

        // Creating a AnchorNode with a specific anchor
        AnchorNode anchorNode = new AnchorNode(anchor);

        // attaching the anchorNode with the ArFragment
        anchorNode.setParent(arCam.getArSceneView().getScene());

        // attaching the anchorNode with the TransformableNode
        TransformableNode model = new TransformableNode(arCam.getTransformationSystem());
        model.setParent(anchorNode);

        // attaching the 3d model with the TransformableNode
        // that is already attached with the node
        model.setRenderable(modelRenderable);
        model.select();
    }

    public static boolean checkSystemSupport(Activity activity) {

        // checking whether the API version of the running Android >= 24
        // that means Android Nougat 7.0
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
            String openGlVersion = ((ActivityManager) Objects.requireNonNull(activity.getSystemService(Context.ACTIVITY_SERVICE))).getDeviceConfigurationInfo().getGlEsVersion();

            // checking whether the OpenGL version >= 3.0
            if (Double.parseDouble(openGlVersion) >= 3.0) {
                return true;
            } else {
                Toast.makeText(activity, "App needs OpenGl Version 3.0 or later", Toast.LENGTH_SHORT).show();
                activity.finish();
                return false;
            }
        } else {
            Toast.makeText(activity, "App does not support required Build Version", Toast.LENGTH_SHORT).show();
            activity.finish();
            return false;
        }
    }
}