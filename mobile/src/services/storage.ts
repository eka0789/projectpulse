import { Storage } from "@ionic/storage";

import type { Session } from "../types";

const SESSION_KEY = "projectpulse.session";
const storage = new Storage({
  name: "__projectpulse",
  storeName: "session",
});
let ready: Promise<Storage> | null = null;

function getStorage(): Promise<Storage> {
  ready ??= storage.create();
  return ready;
}

export async function getSession(): Promise<Session | null> {
  const driver = await getStorage();
  return (await driver.get(SESSION_KEY)) as Session | null;
}

export async function setSession(session: Session): Promise<void> {
  const driver = await getStorage();
  await driver.set(SESSION_KEY, session);
}

export async function clearSession(): Promise<void> {
  const driver = await getStorage();
  await driver.remove(SESSION_KEY);
}

