import { useEffect, useRef, useState } from 'react';
import LetterGlitch from './LetterGlitch';
import FaultyTerminal from './FaultyTerminal';

const BG = '#f8f6fa';
const GLITCH_COLORS = ['#d11872', '#e63d8a', '#F43F5E', '#9d1252', '#c4bdcf'];

function randomBetween(min, max) {
    return min + Math.random() * (max - min);
}

export default function SponsorDecayBackground() {
    const [phase, setPhase] = useState('letters');
    const [glitchSpeed, setGlitchSpeed] = useState(40);
    const [glitchAmount, setGlitchAmount] = useState(1.4);
    const [flickerAmount, setFlickerAmount] = useState(1.2);
    const timeoutRef = useRef(null);

    useEffect(() => {
        function scheduleNext(currentPhase) {
            if (currentPhase === 'letters') {
                timeoutRef.current = setTimeout(() => {
                    setPhase('terminal');
                    setGlitchSpeed(25);
                    setGlitchAmount(1.8);
                    setFlickerAmount(1.5);
                    scheduleNext('terminal');
                }, randomBetween(5000, 7000));
            } else {
                timeoutRef.current = setTimeout(() => {
                    setPhase('letters');
                    setGlitchSpeed(40);
                    setGlitchAmount(1.4);
                    setFlickerAmount(1.2);
                    scheduleNext('letters');
                }, randomBetween(2000, 3000));
            }
        }

        scheduleNext('letters');

        return () => {
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
        };
    }, []);

    const lettersVisible = phase === 'letters';
    const terminalVisible = phase === 'terminal';

    return (
        <div className="relative w-full h-full overflow-hidden" style={{ backgroundColor: BG }}>
            <div
                className="absolute inset-0 transition-opacity duration-[800ms] ease-in-out"
                style={{ opacity: lettersVisible ? 1 : 0 }}
            >
                <LetterGlitch
                    glitchColors={GLITCH_COLORS}
                    glitchSpeed={glitchSpeed}
                    outerVignette={false}
                    centerVignette={false}
                    backgroundColor={BG}
                />
            </div>
            <div
                className="absolute inset-0 transition-opacity duration-[800ms] ease-in-out"
                style={{ opacity: terminalVisible ? 1 : 0 }}
            >
                <FaultyTerminal
                    tint="#d11872"
                    backgroundColor={BG}
                    brightness={0.85}
                    glitchAmount={glitchAmount}
                    flickerAmount={flickerAmount}
                    scanlineIntensity={0.5}
                    noiseAmp={1.2}
                    curvature={0.15}
                    pageLoadAnimation={false}
                    mouseReact={false}
                />
            </div>
        </div>
    );
}
